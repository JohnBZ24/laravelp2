<?php

namespace App\Services;

class EmbeddingService
{
    /**
     * @return array<int, string>
     */
    public function chunkText(string $text, int $targetLength = 1600, int $overlap = 200): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($text === '') {
            return [];
        }

        $chunks = [];
        $length = strlen($text);
        $cursor = 0;

        while ($cursor < $length) {
            $end = min($cursor + $targetLength, $length);
            $slice = substr($text, $cursor, $end - $cursor);
            $chunks[] = trim($slice);

            if ($end >= $length) {
                break;
            }

            $cursor = max($end - $overlap, $cursor + 1);
        }

        return array_values(array_filter($chunks, fn (string $chunk): bool => $chunk !== ''));
    }

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $text = mb_strtolower(trim($text));

        if ($text === '') {
            return [0.0, 0.0, 0.0, 0.0];
        }

        $tokens = preg_split('/\s+/', $text) ?: [];
        $vector = [0.0, 0.0, 0.0, 0.0];

        foreach ($tokens as $token) {
            $hash = crc32($token);
            $vector[0] += ($hash & 0xFF) / 255;
            $vector[1] += (($hash >> 8) & 0xFF) / 255;
            $vector[2] += (($hash >> 16) & 0xFF) / 255;
            $vector[3] += (($hash >> 24) & 0xFF) / 255;
        }

        $norm = sqrt(array_reduce($vector, fn (float $carry, float $value): float => $carry + ($value ** 2), 0.0));

        if ($norm <= 0.0) {
            return $vector;
        }

        return array_map(fn (float $value): float => $value / $norm, $vector);
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $len = min(count($a), count($b));
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
