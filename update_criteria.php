<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$criteria = DB::table('admission_scoring_criteria')->get();

// 1. Gộp nhóm các tiêu chí cũ
foreach ($criteria as $c) {
    $category = 'Chung'; // Mặc định
    
    // Phân tích theo data_field
    if (strpos($c->data_field, 'profile.phone') !== false || strpos($c->data_field, 'profile.email') !== false) {
        $category = 'Thông tin cá nhân';
    } elseif (strpos($c->data_field, 'profile.major') !== false || strpos($c->data_field, 'profile.intent') !== false) {
        $category = 'Nhu cầu học tập';
    } elseif (strpos($c->data_field, 'activity.') !== false) {
        $category = 'Tương tác mạng xã hội';
    }

    DB::table('admission_scoring_criteria')->where('id', $c->id)->update(['category' => $category]);
}

// 2. Xóa các tiêu chí Tương tác MXH bị trùng lặp (nếu có) trước khi tạo mới để tránh lỗi
DB::table('admission_scoring_criteria')->where('category', 'Tương tác mạng xã hội')->delete();

// 3. Thêm các tiêu chí Tương tác MXH mới
$newCriteria = [
    [
        'category' => 'Tương tác mạng xã hội',
        'criterion_code' => 'FB_LIKE_POST',
        'criterion_name' => 'Có Like bài viết',
        'data_field' => 'activity.like_count',
        'input_type' => 'number',
        'operator' => '>=',
        'comparison_value' => '1',
        'score' => 5,
        'priority' => 1,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'category' => 'Tương tác mạng xã hội',
        'criterion_code' => 'FB_SHARE_POST',
        'criterion_name' => 'Có Share bài viết',
        'data_field' => 'activity.share_count',
        'input_type' => 'number',
        'operator' => '>=',
        'comparison_value' => '1',
        'score' => 10,
        'priority' => 2,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'category' => 'Tương tác mạng xã hội',
        'criterion_code' => 'FB_COMMENT_POST',
        'criterion_name' => 'Có Comment bài viết',
        'data_field' => 'activity.comment_count',
        'input_type' => 'number',
        'operator' => '>=',
        'comparison_value' => '1',
        'score' => 15,
        'priority' => 3,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'category' => 'Tương tác mạng xã hội',
        'criterion_code' => 'FB_CHAT_MESSENGER',
        'criterion_name' => 'Nhắn tin qua Messenger/Zalo',
        'data_field' => 'activity.chat_count',
        'input_type' => 'number',
        'operator' => '>=',
        'comparison_value' => '1',
        'score' => 20,
        'priority' => 4,
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ],
];

DB::table('admission_scoring_criteria')->insert($newCriteria);

echo "Updated and inserted criteria successfully!\n";
