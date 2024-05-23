<?php

namespace Moodle;

use Exception;

class MoodleThemeLink
{
    const SOURCE_DIR = 'Themes';
    const TARGET_DIR = 'theme';
    const APP_DIR = 'app';
    const PUBLIC_DIR = 'public';

    /**
     * @throws Exception
     */
    public static function handleThemeLink(): void
    {
        $themes = scandir(self::APP_DIR . '/' . self::SOURCE_DIR);

        if ($themes) {
            $appDir = getcwd();

            foreach ($themes as $theme) {
                if ($theme === '.' || $theme === '..') {
                    continue;
                }

                $sourcePath = $appDir . '/' . self::APP_DIR . '/' . self::SOURCE_DIR . '/' . $theme;
                $targetPath = $appDir . '/' . self::PUBLIC_DIR . '/' . self::TARGET_DIR . '/' . $theme;

                if (is_link($targetPath)) {
                    unlink($targetPath);
                }

                symlink($sourcePath, $targetPath);
                echo "Linked theme: $theme\n";
            }
        } else {
            echo "No themes linked.";
        }


    }
}