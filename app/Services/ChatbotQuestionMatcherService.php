<?php

namespace App\Services;

class ChatbotQuestionMatcherService
{
    /**
     * Exact match theo chuỗi chuẩn hóa tại runtime.
     *
     * Không phụ thuộc question_hash hoặc metadata đã lưu trước đó.
     */
    public function isExactMatch(
        array $queryAnalysis,
        array $candidateAnalysis
    ) {
        return isset(
            $queryAnalysis['normalized'],
            $candidateAnalysis['normalized']
        )
            && $queryAnalysis['normalized'] !== ''
            && hash_equals(
                (string) $queryAnalysis['normalized'],
                (string) $candidateAnalysis['normalized']
            );
    }

    public function compare(
        array $queryAnalysis,
        array $candidateAnalysis
    ) {
        $queryTokens = $queryAnalysis['tokens'];
        $candidateTokens = $candidateAnalysis['tokens'];

        if (empty($queryTokens) || empty($candidateTokens)) {
            return $this->emptyResult();
        }

        $exactCommon = array_values(array_intersect(
            $queryTokens,
            $candidateTokens
        ));

        $fuzzyPairs = $this->fuzzyTokenPairs(
            $queryTokens,
            $candidateTokens,
            $exactCommon
        );

        $acronymPairs = $this->acronymPairs(
            $queryTokens,
            $candidateTokens
        );

        $matchedQueryTokens = $exactCommon;

        foreach ($fuzzyPairs as $pair) {
            $matchedQueryTokens[] = $pair['query'];
        }

        foreach ($acronymPairs as $pair) {
            $matchedQueryTokens[] = $pair['query'];
        }

        $matchedQueryTokens = array_values(array_unique(
            $matchedQueryTokens
        ));

        $matchedCandidateTokens = $exactCommon;

        foreach ($fuzzyPairs as $pair) {
            $matchedCandidateTokens[] = $pair['candidate'];
        }

        foreach ($acronymPairs as $pair) {
            foreach ($pair['candidate_tokens'] as $token) {
                $matchedCandidateTokens[] = $token;
            }
        }

        $matchedCandidateTokens = array_values(array_unique(
            $matchedCandidateTokens
        ));

        $queryCount = count($queryTokens);
        $candidateCount = count($candidateTokens);
        $commonCount = count($exactCommon);

        $queryCoverage = count($matchedQueryTokens)
            / max(1, $queryCount);
        $candidateCoverage = count($matchedCandidateTokens)
            / max(1, $candidateCount);

        $union = array_values(array_unique(array_merge(
            $queryTokens,
            $candidateTokens
        )));
        $jaccard = $commonCount / max(1, count($union));

        $charDice = $this->characterNgramDice(
            $queryAnalysis['normalized'],
            $candidateAnalysis['normalized']
        );

        $longestRun = $this->longestCommonTokenRun(
            $queryTokens,
            $candidateTokens
        );

        $shorterCount = max(
            1,
            min($queryCount, $candidateCount)
        );
        $coreRunCoverage = $longestRun / $shorterCount;

        $lengthRatio = min(
            $queryCount,
            $candidateCount
        ) / max(1, max($queryCount, $candidateCount));

        $weightedCoverage = $this->weightedQueryCoverage(
            $queryTokens,
            $matchedQueryTokens
        );

        $strongContainment = count($matchedQueryTokens) >= 3
            && $queryCoverage >= 0.95
            && (
                $longestRun >= 3
                || !empty($acronymPairs)
            );

        $strongCandidateContainment =
            count($matchedCandidateTokens) >= 3
            && $candidateCoverage >= 0.95
            && (
                $longestRun >= 3
                || !empty($acronymPairs)
            );

        $strongCoreMatch = $strongContainment
            || $strongCandidateContainment;

        $score = ($weightedCoverage * 0.34)
            + ($queryCoverage * 0.21)
            + ($candidateCoverage * 0.12)
            + ($jaccard * 0.10)
            + ($charDice * 0.13)
            + ($coreRunCoverage * 0.07)
            + ($lengthRatio * 0.03);

        if ($strongCoreMatch) {
            $score = max(
                $score,
                0.92 + min(
                    0.06,
                    $coreRunCoverage * 0.06
                )
            );
        }

        if (!empty($acronymPairs)) {
            $score = min(1.0, $score + 0.06);
        }

        if (!empty($fuzzyPairs)) {
            $score = min(
                1.0,
                $score + min(0.04, count($fuzzyPairs) * 0.01)
            );
        }

        $minimumCommon = min(3, $queryCount);
        $minimumCommon = max(2, $minimumCommon);

        $standardPass =
            count($matchedQueryTokens) >= $minimumCommon
            && $weightedCoverage >= 0.72
            && $charDice >= 0.38
            && $score >= 0.80;

        return [
            'passes' => $strongCoreMatch || $standardPass,
            'score' => round($score, 6),
            'exact_common_tokens' => $exactCommon,
            'fuzzy_pairs' => $fuzzyPairs,
            'acronym_pairs' => $acronymPairs,
            'matched_query_tokens' => $matchedQueryTokens,
            'matched_candidate_tokens' => $matchedCandidateTokens,
            'query_coverage' => round($queryCoverage, 6),
            'candidate_coverage' => round(
                $candidateCoverage,
                6
            ),
            'weighted_query_coverage' => round(
                $weightedCoverage,
                6
            ),
            'jaccard' => round($jaccard, 6),
            'char_dice' => round($charDice, 6),
            'longest_common_run' => $longestRun,
            'core_run_coverage' => round(
                $coreRunCoverage,
                6
            ),
            'length_ratio' => round($lengthRatio, 6),
            'strong_core_match' => $strongCoreMatch,
        ];
    }

    public function answerFingerprint($answer, $analyzer)
    {
        return hash(
            'sha256',
            $analyzer->normalize($answer)
        );
    }

    private function weightedQueryCoverage(
        array $queryTokens,
        array $matchedTokens
    ) {
        $totalWeight = 0.0;
        $matchedWeight = 0.0;

        foreach ($queryTokens as $token) {
            $weight = max(
                1.0,
                min(3.0, strlen($token) / 3.0)
            );

            $totalWeight += $weight;

            if (in_array($token, $matchedTokens, true)) {
                $matchedWeight += $weight;
            }
        }

        return $matchedWeight / max(1.0, $totalWeight);
    }

    /**
     * Ghép lỗi chính tả nhẹ theo khoảng cách Levenshtein.
     * Chỉ áp dụng token dài, sai tối đa một ký tự.
     */
    private function fuzzyTokenPairs(
        array $queryTokens,
        array $candidateTokens,
        array $exactCommon
    ) {
        $pairs = [];
        $usedCandidates = [];

        foreach ($queryTokens as $queryToken) {
            if (
                in_array($queryToken, $exactCommon, true)
                || strlen($queryToken) < 5
            ) {
                continue;
            }

            foreach ($candidateTokens as $candidateToken) {
                if (
                    isset($usedCandidates[$candidateToken])
                    || in_array(
                        $candidateToken,
                        $exactCommon,
                        true
                    )
                    || strlen($candidateToken) < 5
                ) {
                    continue;
                }

                if (
                    abs(
                        strlen($queryToken)
                        - strlen($candidateToken)
                    ) > 1
                ) {
                    continue;
                }

                $distance = levenshtein(
                    $queryToken,
                    $candidateToken
                );

                if ($distance <= 1) {
                    $pairs[] = [
                        'query' => $queryToken,
                        'candidate' => $candidateToken,
                        'distance' => $distance,
                    ];
                    $usedCandidates[$candidateToken] = true;
                    break;
                }
            }
        }

        return $pairs;
    }

    /**
     * Nhận diện viết tắt tổng quát.
     *
     * Ví dụ token "cntt" có thể khớp với chuỗi token
     * có chữ cái đầu tương ứng mà không hard-code từ viết tắt.
     */
    private function acronymPairs(
        array $queryTokens,
        array $candidateTokens
    ) {
        $pairs = [];

        foreach ($queryTokens as $queryToken) {
            if (
                strlen($queryToken) < 2
                || strlen($queryToken) > 8
            ) {
                continue;
            }

            $candidateCount = count($candidateTokens);

            for ($start = 0; $start < $candidateCount; $start++) {
                $initials = '';
                $sequence = [];

                for (
                    $index = $start;
                    $index < $candidateCount
                        && count($sequence) < 8;
                    $index++
                ) {
                    $sequence[] = $candidateTokens[$index];
                    $initials .= substr(
                        $candidateTokens[$index],
                        0,
                        1
                    );

                    if (
                        count($sequence) >= 2
                        && $initials === $queryToken
                    ) {
                        $pairs[] = [
                            'query' => $queryToken,
                            'candidate_tokens' => $sequence,
                        ];
                        break 2;
                    }
                }
            }
        }

        return $pairs;
    }

    private function longestCommonTokenRun(
        array $leftTokens,
        array $rightTokens
    ) {
        $leftCount = count($leftTokens);
        $rightCount = count($rightTokens);

        if ($leftCount === 0 || $rightCount === 0) {
            return 0;
        }

        $previous = array_fill(0, $rightCount + 1, 0);
        $maximum = 0;

        for ($left = 1; $left <= $leftCount; $left++) {
            $current = array_fill(0, $rightCount + 1, 0);

            for ($right = 1; $right <= $rightCount; $right++) {
                if (
                    $leftTokens[$left - 1]
                    !== $rightTokens[$right - 1]
                ) {
                    continue;
                }

                $current[$right] =
                    $previous[$right - 1] + 1;
                $maximum = max(
                    $maximum,
                    $current[$right]
                );
            }

            $previous = $current;
        }

        return $maximum;
    }

    private function characterNgramDice($left, $right)
    {
        $left = str_replace(' ', '', (string) $left);
        $right = str_replace(' ', '', (string) $right);

        if ($left === '' || $right === '') {
            return 0.0;
        }

        if ($left === $right) {
            return 1.0;
        }

        $leftNgrams = $this->characterNgrams($left, 3);
        $rightNgrams = $this->characterNgrams($right, 3);

        if (empty($leftNgrams) || empty($rightNgrams)) {
            return 0.0;
        }

        $intersection = count(array_intersect(
            $leftNgrams,
            $rightNgrams
        ));

        return (2 * $intersection)
            / max(
                1,
                count($leftNgrams) + count($rightNgrams)
            );
    }

    private function characterNgrams($text, $size)
    {
        $length = strlen($text);

        if ($length <= $size) {
            return [$text];
        }

        $ngrams = [];

        for ($index = 0; $index <= $length - $size; $index++) {
            $ngrams[] = substr($text, $index, $size);
        }

        return array_values(array_unique($ngrams));
    }

    private function emptyResult()
    {
        return [
            'passes' => false,
            'score' => 0.0,
            'exact_common_tokens' => [],
            'fuzzy_pairs' => [],
            'acronym_pairs' => [],
            'matched_query_tokens' => [],
            'matched_candidate_tokens' => [],
            'query_coverage' => 0.0,
            'candidate_coverage' => 0.0,
            'weighted_query_coverage' => 0.0,
            'jaccard' => 0.0,
            'char_dice' => 0.0,
            'longest_common_run' => 0,
            'core_run_coverage' => 0.0,
            'length_ratio' => 0.0,
            'strong_core_match' => false,
        ];
    }
}
