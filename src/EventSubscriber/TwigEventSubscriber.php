<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use App\Repository\CategoryRepository;
use Twig\Environment;

class TwigEventSubscriber implements EventSubscriberInterface
{

    private $twig;
    private $categoryRepository;

    public function __construct(Environment $twig, CategoryRepository $categoryRepository)
    {
        $this->twig = $twig;
        $this->categoryRepository = $categoryRepository;
        
    }

    public function onControllerEvent(ControllerEvent $event): void
    {
        $this->twig->addGlobal('categories', $this->categoryRepository->findAll());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => 'onControllerEvent',
        ];
    }
}
