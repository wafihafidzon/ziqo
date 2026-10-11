<?php
// Helper sederhana untuk baca/tulis seluruh data ZIQO ke data.json

// Baca seluruh data dari data.json
function loadData() {
    $kosong = [
        'users' => [], 'categories' => [], 'quizzes' => [],
        'questions' => [], 'rooms' => [], 'attempts' => [],
        'next_id' => [
            'users' => 1, 'categories' => 1, 'quizzes' => 1,
            'questions' => 1, 'rooms' => 1, 'attempts' => 1,
        ],
    ];
    if (!file_exists(__DIR__ . '/data.json')) {
        return $kosong;
    }
    $json = file_get_contents(__DIR__ . '/data.json');
    if ($json === false) {
        return $kosong;
    }
    return json_decode($json, true) ?? $kosong;
}

// Simpan seluruh data ke data.json
function saveData($data) {
    file_put_contents(
        __DIR__ . '/data.json',
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

// Cari user berdasarkan email (untuk login & register)
function cariUserByEmail($users, $email) {
    foreach ($users as $user) {
        if ($user['email'] === $email) return $user;
    }
    return null;
}
