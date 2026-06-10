<?php

namespace App\Controller;

use App\Dto\UrlStoreRequest;
use App\Entity\Click;
use App\Entity\Url;
use App\Repository\ClickRepository;
use App\Repository\UrlRepository;
use App\Service\CodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use WhichBrowser\Parser;

class UrlController extends AbstractController
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
        private readonly ClickRepository $clickRepository,
        private readonly UrlRepository $urlRepository,
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('url/store.html.twig');
    }

    #[Route('/url', name: 'url_store', methods: ['POST'])]
    public function store(
        Request $request,
        ValidatorInterface $validator,
    ): Response {
        $dto = new UrlStoreRequest();
        $dto->url = trim((string) $request->request->get('url'));
        $dto->ttl = (int) $request->request->get('ttl', 0);

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->render('url/store.html.twig', [
                'error' => $errors->get(0)->getMessage(),
                'old_url' => $dto->url,
                'old_ttl' => $dto->ttl,
            ]);
        }

        $url = new Url();
        $url->setOriginal($dto->url);

        if ($dto->ttl > 0) {
            $url->setExpiresAt(new \DateTimeImmutable("+{$dto->ttl} days"));
        }

        $url->setShortCode($this->codeGenerator->generate());
        $this->urlRepository->save($url, true);

        return $this->redirectToRoute('statistic_show', ['code' => $url->getShortCode()]);
    }

    #[Route('/{code}', name: 'url_redirect', requirements: ['code' => '[0-9a-zA-Z]{6}'], methods: ['GET'])]
    public function shortUrlRedirect(
        string $code,
        Request $request,
    ): Response {
        $url = $this->urlRepository->findByShortCode($code);

        if (null === $url) {
            throw $this->createNotFoundException('Link not found for code '.$code);
        }

        if ($url->isExpired()) {
            return $this->render('url/expired.html.twig', ['url' => $url])
                ->setStatusCode(Response::HTTP_GONE);
        }

        $browserParser = new Parser($request->headers->get('User-Agent'));
        $click = new Click($url);
        $click->setIp($request->getClientIp())
            ->setUserAgent($request->headers->get('User-Agent'))
            ->setReferer($request->headers->get('Referer'))
            ->setBrowser($browserParser->browser?->name)
            ->setDevice($browserParser->device?->type);

        $this->clickRepository->save($click, true);

        return $this->redirect($url->getOriginal(), Response::HTTP_FOUND);
    }
}
