<?php

namespace App\Controller;

use App\Repository\ClickRepository;
use App\Repository\UrlRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatisticController extends AbstractController
{
    public function __construct(
        private readonly ClickRepository $clickRepository,
        private readonly UrlRepository $urlRepository,
    ) {}

    #[Route('/statistic/{code}', name: 'statistic_show', methods: ['GET'])]
    public function show(string $code): Response
    {
        $url = $this->urlRepository->findByShortCode($code);

        if ($url === null) {
            throw $this->createNotFoundException('Link not found by short code ' . $code);
        }

        return $this->render('statistic/show.html.twig', [
            'url' => $url,
            'totalCount' => $this->clickRepository->countByUrl($url),
            'uniqueCount' => $this->clickRepository->countUniqueByUrl($url),
            'clicksByDay' => $this->clickRepository->countByUrlGroupedByDay($url),
            'clicksByReferer' => $this->clickRepository->countByUrlGroupedByReferer($url),
            'clicksByBrowser' => $this->clickRepository->countByUrlGroupedByBrowser($url),
            'clicksByDevice' => $this->clickRepository->countByUrlGroupedByDevice($url),
        ]);
    }
}
