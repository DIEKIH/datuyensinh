<?php

return [
    'context' => [
        'max_messages' => 12,
        'max_characters' => 16000,
    ],

    'semantic' => [
        'enabled' => env(
            'CHATBOT_SEMANTIC_MATCHING',
            true
        ),
        'model' => env(
            'CHATBOT_EMBEDDING_MODEL',
            'text-embedding-3-small'
        ),
        'timeout' => 20,
        'connect_timeout' => 5,
    ],

    'matcher' => [
        /*
         * Quét toàn bộ kho nếu số bản ghi nhỏ hơn giới hạn này.
         * Không lọc cứng theo chủ đề hoặc tên ngành.
         */
        'max_candidates' => 1000,

        /*
         * Quét runtime cho exact khi metadata cũ.
         */
        'exact_runtime_scan_limit' => 5000,

        'lexical_threshold' => 0.90,
        'semantic_threshold' => 0.88,

        'hybrid_semantic_floor' => 0.78,
        'hybrid_lexical_floor' => 0.25,
        'hybrid_threshold' => 0.80,

        /*
         * Chênh lệch tối thiểu giữa hai đáp án khác nội dung.
         */
        'minimum_margin' => 0.03,
    ],
];
