<?php

class ProfanityFilter {
    private $badWords = [
        // Hakaret içeren kelimeler
        'amk', 'aq', 'mal', 'salak', 'gerizekalı', 'aptal',
        
        // Spam belirteçleri
        'kazandiniz', 'bedava', 'kredi', 'casino', 'bahis', 'betting',
        'viagra', 'cialis', 'porn', 'sex', 'dating', 'lottery',
        'prize', 'winner', 'won', 'bitcoin', 'crypto', 'investment',
        'mortgage', 'loan', 'credit', 'free', 'cheap', 'discount',
        
        // Link spam belirteçleri
        'bit.ly', 'tinyurl', 'goo.gl', 'ow.ly', 't.co',
        
        // Türkçe spam belirteçleri
        'tikla', 'kazan', 'para', 'zengin', 'kolay', 'hemen',
        'firssat', 'firsat', 'kampanya', 'indirim', 'bedava',
        'kazanc', 'yatirim', 'garanti', 'bonus'
    ];

    private $spamPatterns = [
        '/\b(www\.|http:\/\/|https:\/\/)/i',          // URL'ler
        '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', // Email adresleri
        '/\b\d{10,}\b/',                              // Uzun sayılar
        '/(.)\1{4,}/',                                // Tekrarlanan karakterler
        '/\b[A-Z]{7,}\b/',                            // Büyük harfli kelimeler
        '/[\$€₺]{2,}/',                               // Para birimleri
        '/\b(whatsapp|telegram|signal)\b/i'           // Mesajlaşma uygulamaları
    ];

    private $replacements = [
        '*', '#', '@', '$', '&'
    ];

    public function filter($text) {
        // Kötü kelimeleri filtrele
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $this->badWords)) . ')\b/iu';
        
        $text = preg_replace_callback($pattern, function($matches) {
            $word = $matches[0];
            $len = mb_strlen($word);
            $replacement = '';
            
            for ($i = 0; $i < $len; $i++) {
                $replacement .= $this->replacements[array_rand($this->replacements)];
            }
            
            return $replacement;
        }, $text);

        return $text;
    }

    public function isSpam($text) {
        // Kötü kelime kontrolü
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $this->badWords)) . ')\b/iu';
        if (preg_match($pattern, $text)) {
            return true;
        }

        // Spam pattern kontrolü
        foreach ($this->spamPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        // Link sayısı kontrolü
        $urlCount = preg_match_all('/(http|https|www\.|[a-z0-9][a-z0-9-]*[a-z0-9]\.[a-z]{2,})/i', $text);
        if ($urlCount > 2) {
            return true;
        }

        // Büyük harf oranı kontrolü
        $upperCount = strlen(preg_replace('/[^A-Z]/', '', $text));
        $totalCount = strlen(preg_replace('/[^a-zA-Z]/', '', $text));
        if ($totalCount > 0 && ($upperCount / $totalCount) > 0.6) {
            return true;
        }

        return false;
    }

    public function hasProfanity($text) {
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $this->badWords)) . ')\b/iu';
        return preg_match($pattern, $text) === 1;
    }

    public function getSpamScore($text) {
        $score = 0;
        
        // Kötü kelime sayısı
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $this->badWords)) . ')\b/iu';
        $score += preg_match_all($pattern, $text) * 2;

        // Spam pattern sayısı
        foreach ($this->spamPatterns as $pattern) {
            $score += preg_match_all($pattern, $text);
        }

        // Link sayısı
        $urlCount = preg_match_all('/(http|https|www\.|[a-z0-9][a-z0-9-]*[a-z0-9]\.[a-z]{2,})/i', $text);
        $score += $urlCount * 2;

        // Büyük harf oranı
        $upperCount = strlen(preg_replace('/[^A-Z]/', '', $text));
        $totalCount = strlen(preg_replace('/[^a-zA-Z]/', '', $text));
        if ($totalCount > 0 && ($upperCount / $totalCount) > 0.6) {
            $score += 3;
        }

        // Tekrarlanan karakterler
        if (preg_match('/(.)\1{4,}/', $text)) {
            $score += 2;
        }

        return $score;
    }
} 