<?php

namespace PicPerf\Breakdown\Lib;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Renderer {
    public static function view(array $data) {
        $absolutePath = realpath(__DIR__.'/../../resources/views');
        $loader = new FilesystemLoader($absolutePath);
        $twig = new Environment($loader);

        return $twig->render('output.html.twig', $data);
    }
}
