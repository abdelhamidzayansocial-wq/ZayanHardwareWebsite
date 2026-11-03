<?php
declare(strict_types=1);

use App\Services\AudioWavPolicy;

require dirname(__DIR__, 2) . '/app/services/AudioWavPolicy.php';

function makeTestWav(int $sampleRate = 16000, int $channels = 1, int $bits = 16, int $durationMs = 1000): string
{
    $bytesPerSample = intdiv($bits, 8);
    $dataSize = (int) ($sampleRate * $durationMs / 1000) * $channels * $bytesPerSample;
    $blockAlign = $channels * $bytesPerSample;
    $fmt = pack('vvVVvv', 1, $channels, $sampleRate, $sampleRate * $blockAlign, $blockAlign, $bits);
    $data = str_repeat(chr(0), $dataSize);

    return 'RIFF' . pack('V', 36 + $dataSize) . 'WAVE'
        . 'fmt ' . pack('V', 16) . $fmt
        . 'data' . pack('V', $dataSize) . $data;
}

function makeChunk(string $id, string $payload, bool $includePadding = true): string
{
    return $id . pack('V', strlen($payload)) . $payload
        . ($includePadding && strlen($payload) % 2 ? chr(0) : '');
}

function makeContainer(string ...$chunks): string
{
    $body = 'WAVE' . implode('', $chunks);
    return 'RIFF' . pack('V', strlen($body)) . $body;
}

function expectRejected(string $bytes, string $label): void
{
    $path = tempnam(sys_get_temp_dir(), 'zayan-audio-invalid-');
    if ($path === false) throw new RuntimeException('Unable to create test file.');
    file_put_contents($path, $bytes);
    try {
        AudioWavPolicy::inspect($path);
        throw new RuntimeException('Expected WAV rejection: ' . $label);
    } catch (DomainException) {
        // Expected validation failure.
    } finally {
        @unlink($path);
    }
}

$path = tempnam(sys_get_temp_dir(), 'zayan-audio-valid-');
if ($path === false) throw new RuntimeException('Unable to create test file.');
file_put_contents($path, makeTestWav());
try {
    $result = AudioWavPolicy::inspect($path);
    if ($result['mime_type'] !== 'audio/wav' || $result['duration_ms'] !== 1000 || $result['file_size'] < 32044) {
        throw new RuntimeException('Valid WAV metadata was not parsed correctly.');
    }
} finally {
    @unlink($path);
}

$fmt = makeChunk('fmt ', pack('vvVVvv', 1, 1, 16000, 32000, 2, 16));
$data = makeChunk('data', str_repeat(chr(0), 32000));
$padded = makeContainer($fmt, makeChunk('JUNK', 'x'), $data);
$paddedPath = tempnam(sys_get_temp_dir(), 'zayan-audio-padded-');
if ($paddedPath === false) throw new RuntimeException('Unable to create padded WAV fixture.');
file_put_contents($paddedPath, $padded);
try {
    $paddedResult = AudioWavPolicy::inspect($paddedPath);
    if ($paddedResult['duration_ms'] !== 1000) {
        throw new RuntimeException('Valid odd-sized padded chunk duration was parsed incorrectly.');
    }
} finally {
    @unlink($paddedPath);
}

$trailing = makeTestWav();
$trailing .= chr(0);
$trailing = substr($trailing, 0, 4) . pack('V', strlen($trailing) - 8) . substr($trailing, 8);
expectRejected('not-a-wave-file', 'invalid RIFF header');
expectRejected(makeTestWav(44100), 'wrong sample rate');
expectRejected(makeTestWav(16000, 2), 'stereo audio');
expectRejected(makeTestWav(16000, 1, 8), '8-bit PCM');
expectRejected($trailing, 'trailing junk byte inside RIFF boundary');
expectRejected(makeContainer($fmt, makeChunk('JUNK', 'x', false), $data), 'missing odd-chunk padding');
expectRejected(makeContainer($fmt, $fmt, $data), 'duplicate fmt chunk');
expectRejected(makeContainer($fmt, $data, $data), 'duplicate data chunk');
expectRejected(makeContainer($fmt, 'data' . pack('V', 32000) . str_repeat(chr(0), 12)), 'truncated data chunk');
$wrongRiffSize = makeTestWav();
$wrongRiffSize = substr($wrongRiffSize, 0, 4) . pack('V', strlen($wrongRiffSize) - 10) . substr($wrongRiffSize, 8);
expectRejected($wrongRiffSize, 'RIFF declared size mismatch');
expectRejected(makeTestWav(16000, 1, 16, 50), 'duration below minimum');

$oversizedPath = tempnam(sys_get_temp_dir(), 'zayan-audio-oversized-');
if ($oversizedPath === false) throw new RuntimeException('Unable to create oversized WAV fixture.');
$oversized = fopen($oversizedPath, 'wb');
if ($oversized === false) throw new RuntimeException('Unable to write oversized WAV fixture.');
fwrite($oversized, 'RIFF' . pack('V', AudioWavPolicy::MAX_BYTES) . 'WAVE');
ftruncate($oversized, AudioWavPolicy::MAX_BYTES + 1);
fclose($oversized);
try {
    try {
        AudioWavPolicy::inspect($oversizedPath);
        throw new RuntimeException('Expected oversized WAV rejection.');
    } catch (DomainException) {
        // Expected size rejection.
    }
} finally {
    @unlink($oversizedPath);
}

echo "Audio WAV policy validates RIFF boundaries, padding, unique chunks, size, duration, and mono 16 kHz PCM16 format.\n";
