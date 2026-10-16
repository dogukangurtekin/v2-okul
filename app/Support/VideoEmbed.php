<?php

namespace App\Support;

class VideoEmbed
{
    /** YouTube/Vimeo baglantisini gomulu oynatici adresine cevirir; tanimsizsa null. */
    public static function embedUrl(string $url): ?string
    {
        if (preg_match('#^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }
        if (preg_match('#^https?://(?:www\.)?vimeo\.com/(\d{5,12})#i', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }
}
