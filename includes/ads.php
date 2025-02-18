<?php
class AdManager {
    private $auth;
    private $adSlots = [
        'header' => '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-XXXXXXXXXXXXXXXX" data-ad-slot="XXXXXXXXXX" data-ad-format="auto" data-full-width-responsive="true"></ins>',
        'sidebar' => '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-XXXXXXXXXXXXXXXX" data-ad-slot="XXXXXXXXXX" data-ad-format="auto" data-full-width-responsive="true"></ins>',
        'content' => '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-XXXXXXXXXXXXXXXX" data-ad-slot="XXXXXXXXXX" data-ad-format="auto" data-full-width-responsive="true"></ins>',
        'mobile' => '<ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-XXXXXXXXXXXXXXXX" data-ad-slot="XXXXXXXXXX" data-ad-format="auto" data-full-width-responsive="true"></ins>'
    ];

    public function __construct($auth) {
        $this->auth = $auth;
    }

    public function shouldShowAds() {
        // Giriş yapmış kullanıcılar için reklam gösterim oranını azalt
        if ($this->auth->isLoggedIn()) {
            return (rand(1, 100) <= 30); // %30 olasılık
        }
        return true; // Anonim kullanıcılar için her zaman göster
    }

    public function getAdCode($position) {
        if (!$this->shouldShowAds()) {
            return '';
        }

        if (!isset($this->adSlots[$position])) {
            return '';
        }

        $adCode = "<!-- Reklam: $position -->\n";
        $adCode .= '<div class="ad-container ad-' . $position . '">';
        $adCode .= $this->adSlots[$position];
        $adCode .= '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
        $adCode .= "</div>\n";

        return $adCode;
    }

    public function getAdScript() {
        return '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>';
    }

    public function getAdStyle() {
        return '
        <style>
        .ad-container {
            text-align: center;
            margin: 1rem 0;
            min-height: 90px;
            overflow: hidden;
        }
        .ad-header {
            margin-bottom: 2rem;
        }
        .ad-sidebar {
            margin: 1rem 0;
        }
        .ad-content {
            margin: 2rem 0;
        }
        @media (max-width: 768px) {
            .ad-sidebar {
                display: none;
            }
            .ad-mobile {
                display: block;
            }
        }
        @media (min-width: 769px) {
            .ad-mobile {
                display: none;
            }
        }
        </style>';
    }
} 