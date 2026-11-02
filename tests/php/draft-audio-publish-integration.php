<?php
declare(strict_types=1);

use App\Core\App;
use App\Models\Database;
use App\Models\PostDraft;
use App\Services\DraftPublishingService;

if (PHP_SAPI !== 'cli' || (string) getenv('DB_NAME') !== 'zayan_fresh_install_test') {
    fwrite(STDERR, "Draft audio publishing integration is restricted to zayan_fresh_install_test.\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
require $root . '/app/core/App.php';
if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        $map = [
            'App\\Core\\' => '/app/core/',
            'App\\Controllers\\' => '/app/controllers/',
            'App\\Models\\' => '/app/models/',
            'App\\Services\\' => '/app/services/',
            'App\\Middleware\\' => '/app/middleware/',
        ];
        foreach ($map as $prefix => $directory) {
            if (!str_starts_with($class, $prefix)) continue;
            $file = $root . $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) require $file;
            return;
        }
    });
}

App::boot($root);
$pdo = Database::connection();
$userId = 1;
$privateRoot = App::root('storage/private');

function expectDraftAudio(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function makeDraftAudioWav(int $durationMs): string
{
    $sampleRate = 16000;
    $frames = (int) ($sampleRate * $durationMs / 1000);
    $data = str_repeat("\0", $frames * 2);
    $dataSize = strlen($data);
    return 'RIFF' . pack('V', 36 + $dataSize) . 'WAVE'
        . 'fmt ' . pack('VvvVVvv', 16, 1, 1, $sampleRate, $sampleRate * 2, 2, 16)
        . 'data' . pack('V', $dataSize) . $data;
}

/** @return array{draft_id:string,media_uuid:string,original:string,rendered:string,original_bytes:int,rendered_bytes:int,title:string} */
function makeDraftAudioFixture(PDO $pdo, int $userId, string $privateRoot, string $title, int $renderedDurationMs = 1500): array
{
    $draftId = PostDraft::newId();
    $mediaUuid = PostDraft::newId();
    $manufacturerId = (int) $pdo->query(
        'SELECT id FROM device_manufacturers WHERE is_active = 1 ORDER BY id LIMIT 1'
    )->fetchColumn();
    if ($manufacturerId < 1) throw new RuntimeException('No active manufacturer fixture exists.');

    $model = $pdo->prepare(
        'INSERT INTO device_models (manufacturer_id, commercial_name, model_code, created_by, approved_by, approved_at)
         VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP())'
    );
    $suffix = strtoupper(bin2hex(random_bytes(4)));
    $model->execute([$manufacturerId, 'Audio CI ' . $suffix, 'AUDIO-' . $suffix, $userId, $userId]);
    $modelId = (int) $pdo->lastInsertId();

    $payload = [
        'content_type' => 'technical_post',
        'details_mode' => 'audio',
        'title' => $title,
        'description' => '',
        'manufacturer_id' => $manufacturerId,
        'device_model_id' => $modelId,
        'category' => '',
        'steps' => [],
        'audio' => [
            'duration_ms' => 2000,
            'trim_start_ms' => 250,
            'trim_end_ms' => 1750,
            'waveform' => [],
        ],
    ];
    $pdo->prepare(
        'INSERT INTO post_drafts (id, user_id, revision, payload_json, last_synced_at)
         VALUES (?, ?, 1, ?, UTC_TIMESTAMP())'
    )->execute([$draftId, $userId, json_encode($payload, JSON_THROW_ON_ERROR)]);

    $directory = $privateRoot . '/drafts/' . $draftId;
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create audio draft directory.');
    }

    // The source is a private original recording. Its bytes are intentionally
    // opaque: publishing must preserve it, not expose it as the rendered audio.
    $originalBytes = 'OggS' . str_repeat('original-audio-fixture-', 20);
    $originalName = $mediaUuid . '.ogg';
    $originalPath = $directory . '/' . $originalName;
    file_put_contents($originalPath, $originalBytes);

    $renderedBytes = makeDraftAudioWav($renderedDurationMs);
    $renderedName = $mediaUuid . '-rendered.wav';
    $renderedPath = $directory . '/' . $renderedName;
    file_put_contents($renderedPath, $renderedBytes);

    $pdo->prepare(
        'INSERT INTO post_draft_media
            (draft_id, media_uuid, media_kind, storage_path, mime_type, file_size, sha256,
             rendered_path, rendered_mime_type, rendered_sha256, editor_state, duration_ms, sort_order)
         VALUES (?, ?, \'audio\', ?, \'audio/ogg\', ?, ?, ?, \'audio/wav\', ?, ?, 2000, 0)'
    )->execute([
        $draftId,
        $mediaUuid,
        'drafts/' . $draftId . '/' . $originalName,
        strlen($originalBytes),
        hash('sha256', $originalBytes),
        'drafts/' . $draftId . '/' . $renderedName,
        hash('sha256', $renderedBytes),
        json_encode([
            'version' => 2,
            'kind' => 'audio-trim',
            'source_duration_basis' => 'decoded-audio',
            'trim_start_ms' => 250,
            'trim_end_ms' => 1750,
            'source_duration_ms' => 2000,
        ], JSON_THROW_ON_ERROR),
    ]);

    return [
        'draft_id' => $draftId,
        'media_uuid' => $mediaUuid,
        'original' => $originalPath,
        'rendered' => $renderedPath,
        'original_bytes' => strlen($originalBytes),
        'rendered_bytes' => strlen($renderedBytes),
        'title' => $title,
    ];
}

try {
    // A failed pre-commit publish must restore both source and rendered audio.
    $rollback = makeDraftAudioFixture($pdo, $userId, $privateRoot, 'Audio rollback ' . bin2hex(random_bytes(4)));
    $rollbackObserved = false;
    try {
        DraftPublishingService::publish(
            $rollback['draft_id'],
            $userId,
            1,
            static function (PDO $connection): void {
                throw new RuntimeException('synthetic audio publish failure before commit');
            }
        );
    } catch (RuntimeException $error) {
        $rollbackObserved = $error->getMessage() === 'synthetic audio publish failure before commit';
    }
    expectDraftAudio($rollbackObserved, 'Injected audio publish failure must be propagated.');
    expectDraftAudio(is_file($rollback['original']), 'Rollback must restore the original recording.');
    expectDraftAudio(is_file($rollback['rendered']), 'Rollback must restore the rendered WAV.');
    $draftExists = $pdo->prepare('SELECT id FROM post_drafts WHERE id = ? AND user_id = ?');
    $draftExists->execute([$rollback['draft_id'], $userId]);
    expectDraftAudio($draftExists->fetchColumn() !== false, 'Rollback must preserve the audio draft.');

    // A materially shorter WAV must be rejected for decoded-duration metadata,
    // and the failed publish must restore both files to draft storage.
    $mismatch = makeDraftAudioFixture($pdo, $userId, $privateRoot, 'Audio mismatch ' . bin2hex(random_bytes(4)), 1000);
    $mismatchObserved = false;
    try {
        DraftPublishingService::publish($mismatch['draft_id'], $userId, 1);
    } catch (DomainException $error) {
        $mismatchObserved = $error->getMessage() === 'draft_audio_render_duration_mismatch';
    }
    expectDraftAudio($mismatchObserved, 'A materially mismatched decoded WAV duration must be rejected.');
    expectDraftAudio(is_file($mismatch['original']), 'Duration mismatch rollback must restore the original recording.');
    expectDraftAudio(is_file($mismatch['rendered']), 'Duration mismatch rollback must restore the rendered WAV.');

    // A successful publish must point the post at the trimmed WAV while keeping
    // the original recording separately in private storage.
    $success = makeDraftAudioFixture($pdo, $userId, $privateRoot, 'Audio publish ' . bin2hex(random_bytes(4)));
    $issueId = DraftPublishingService::publish($success['draft_id'], $userId, 1);
    $issueQuery = $pdo->prepare(
        'SELECT audio_path, audio_original_path, audio_original_mime_type, audio_original_file_size,
                audio_mime_type, audio_file_size, audio_duration_ms, audio_trim_start_ms, audio_trim_end_ms
         FROM issues WHERE id = ? AND user_id = ?'
    );
    $issueQuery->execute([$issueId, $userId]);
    $issue = $issueQuery->fetch(PDO::FETCH_ASSOC);
    expectDraftAudio(is_array($issue), 'Published issue must contain audio metadata.');
    expectDraftAudio($issue['audio_path'] !== '' && $issue['audio_original_path'] !== '', 'Both audio paths must be stored.');
    expectDraftAudio($issue['audio_path'] !== $issue['audio_original_path'], 'Original and rendered paths must be distinct.');
    expectDraftAudio($issue['audio_mime_type'] === 'audio/wav', 'The published audio must be the rendered WAV.');
    expectDraftAudio($issue['audio_original_mime_type'] === 'audio/ogg', 'Original MIME type must be preserved.');
    expectDraftAudio((int) $issue['audio_original_file_size'] === $success['original_bytes'], 'Original size metadata must be preserved.');
    expectDraftAudio((int) $issue['audio_file_size'] === $success['rendered_bytes'], 'Rendered size metadata must match the WAV.');
    expectDraftAudio((int) $issue['audio_duration_ms'] === 1500, 'Published duration must come from inspected WAV data.');
    expectDraftAudio((int) $issue['audio_trim_start_ms'] === 250 && (int) $issue['audio_trim_end_ms'] === 1750,
        'Published trim boundaries must match the confirmed editor state.');

    $originalPublished = $privateRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $issue['audio_original_path']);
    $renderedPublished = $privateRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $issue['audio_path']);
    expectDraftAudio(is_file($originalPublished), 'The original recording must remain in private issue storage.');
    expectDraftAudio(is_file($renderedPublished), 'The rendered WAV must remain in private issue storage.');
    expectDraftAudio(hash_file('sha256', $originalPublished) === hash('sha256', 'OggS' . str_repeat('original-audio-fixture-', 20)),
        'The original recording bytes must remain unchanged.');

    echo "Draft audio publishing integration passed: private original/rendered separation, metadata, and rollback restoration.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Draft audio publishing integration failed: " . $error::class . ' ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
