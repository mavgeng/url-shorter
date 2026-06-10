<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class UrlController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('url/index.html.twig');

    }


}
