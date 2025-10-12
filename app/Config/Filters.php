<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

// custom filter
use App\Filters\AuthFilter;

class Filters extends BaseFilters
{
    /**
     * Aliases untuk filter.
     *
     * [filter_name => classname] atau [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,

        // custom
        'auth'          => AuthFilter::class,
    ];

    /**
     * Filter "required" (selalu diterapkan).
     * Catatan: forcehttps dimatikan sementara karena situs masih HTTP only.
     */
    public array $required = [
        'before' => [
            // 'forcehttps', // aktifkan nanti saat sudah HTTPS
            // 'pagecache',  // aktifkan kalau mau caching halaman statis
        ],
        'after' => [
            'performance', // tampilkan metrik performa (dev)
            'toolbar',     // Debug Toolbar (dev)
            // 'pagecache',
        ],
    ];

    /**
     * Filter global (selalu jalan di semua request).
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            // 'csrf',        // aktifkan nanti saat form sudah fix
            // 'invalidchars',
        ],
        'after' => [
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * Filter per HTTP method (GET, POST, dst.)
     */
    public array $methods = [
        // 'post' => ['csrf'],
    ];

    /**
     * Filter berdasarkan pola URI.
     *
     * Contoh: 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     */
    public array $filters = [
        // Wajib login untuk area berikut:
        'auth' => [
            'before' => [
                'dashboard*',
                'admin*',
                'api*',
                // tambah rute lain kalau perlu:
                // 'nvrs*', 'cameras*', 'users*', 'dashboards*',
            ],
        ],
    ];
}
