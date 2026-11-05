<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Issue;
use App\Services\AuthService;
use App\Services\RateLimiter;

final class SearchController extends Controller
{
    public function index(): Response
    {
        $user = AuthService::user();
        if (!$user) return $this->redirect('/login');

        $query = $this->request->query['q'] ?? '';
        $query = is_string($query) ? trim($query) : '';
        if (mb_strlen($query) > 120) $query = mb_substr($query, 0, 120);

        $type = $this->request->query['type'] ?? '';
        $type = is_string($type) ? $type : '';
        if (!in_array($type, ['help_request', 'technical_post', 'discussion'], true)) $type = '';

        $searchRateLimited = $query !== '' && !RateLimiter::allowForUser('search.technical', (int) $user['id'], 30, 60, 100);
        $results = $query !== '' && !$searchRateLimited ? Issue::search($query, $type) : [];
        foreach ($results as &$item) {
            $tags = json_decode((string) ($item['tags_json'] ?? ''), true);
            $item['tags'] = is_array($tags)
                ? array_values(array_filter($tags, static fn ($tag): bool => is_string($tag)))
                : [];
            $item['device_label'] = trim((string) ($item['manufacturer_name'] ?? '') . ' '
                . (string) ($item['model_commercial_name'] ?? $item['device_model'] ?? ''));
            $item['excerpt'] = mb_strimwidth(trim(strip_tags((string) ($item['description'] ?? ''))), 0, 220, '…', 'UTF-8');
            $item['type_label'] = ($item['content_type'] ?? '') === 'help_request' ? 'طلب مساعدة' : 'معلومة فنية';
        }
        unset($item);

        return $this->view('search/index', [
            'title' => 'البحث الفني',
            'active' => 'search',
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'searchRateLimited' => $searchRateLimited,
        ]);
    }
}
