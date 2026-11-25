<?php

return [
    'required' => ':attribute wajib diisi.',
    'email' => 'Format :attribute tidak valid.',
    'min' => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min.'],
    'max' => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max.'],
    'integer' => ':attribute harus berupa angka bulat.',
    'between' => ['numeric' => ':attribute harus antara :min dan :max.', 'string' => ':attribute harus antara :min dan :max karakter.'],
    'in' => ':attribute tidak valid.',
    'same' => ':attribute tidak sama.',
    'regex' => 'Format :attribute tidak valid.',
];
