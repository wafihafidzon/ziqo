<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Store sesi test dipakai ulang antar request, jadi hash password user sebelumnya tertinggal di sesi.
     * Seperti Auth::login, buang hash itu agar AuthenticateSession menyimpan hash user yang baru
     * (tanpa ini, berganti actingAs di tengah test membuat user baru dikeluarkan).
     */
    public function be(Authenticatable $user, $guard = null)
    {
        parent::be($user, $guard);
        $this->app['session.store']->forget('password_hash_' . $this->app['auth']->getDefaultDriver());
        return $this;
    }
}
