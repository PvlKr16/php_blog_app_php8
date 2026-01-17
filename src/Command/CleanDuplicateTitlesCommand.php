<?php

namespace App\Command;

use App\Document\Post;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-duplicate-titles',
    description: 'Удаляет заголовки постов, которые дублируют содержание',
)]
class CleanDuplicateTitlesCommand extends Command
{
    private DocumentManager $dm;

    public function __construct(DocumentManager $dm)
    {
        parent::__construct();
        $this->dm = $dm;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $posts = $this->dm->getRepository(Post::class)->findAll();

        $cleaned = 0;
        foreach ($posts as $post) {
            if ($post->getTitle() && $post->getTitle() === $post->getContent()) {
                $io->text('Очистка: ' . substr($post->getTitle(), 0, 50) . '...');
                $post->setTitle(null);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            $this->dm->flush();
            $io->success("Очищено заголовков: $cleaned");
        } else {
            $io->info('Дублирующихся заголовков не найдено');
        }

        return Command::SUCCESS;
    }
}