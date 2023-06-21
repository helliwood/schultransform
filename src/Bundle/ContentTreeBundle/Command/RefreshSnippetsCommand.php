<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Trollfjord\Bundle\ContentTreeBundle\Service\SnippetService;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use function array_diff;
use function array_keys;
use function count;
use function date;
use function implode;
use function is_array;
use function is_dir;
use function ksort;
use function opendir;
use function pathinfo;
use function readdir;
use function usleep;

/**
 * Class RefreshSnippetsCommand
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Command
 */
class RefreshSnippetsCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'trollfjord:content-tree-bundle:refresh-snippets';

    /**
     * @var TemplateEngine
     */
    protected TemplateEngine $templateEngine;

    /**
     * @var SnippetService
     */
    protected SnippetService $snippetsService;

    /**
     * RefreshSnippetsCommand constructor.
     * @param TemplateEngine $templateEngine
     * @param SnippetService $snippetsService
     */
    public function __construct(TemplateEngine $templateEngine, SnippetService $snippetsService)
    {
        parent::__construct();
        $this->templateEngine = $templateEngine;
        $this->snippetsService = $snippetsService;
    }

    /**
     * configure
     */
    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Refresh the snippet table.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to refresh snippets...')
            ->addOption("watch", "w", InputOption::VALUE_NONE, "Update Snippets permanently")
            ->addOption("force", "f", InputOption::VALUE_NONE, "Update Snippets in current Content");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ConsoleOutput $output */
        $output->writeln([
            '<info>Trollfjord Web Platform</info>',
            '=======================',
            '<comment>Refreshing Snippets</comment>',
            ''
        ]);
        $section1 = $output->section();

        if ($input->getOption("watch")) {
            $section1->writeln('Watching Snippets...');

            $snippets = [];
            $table = new Table($output);
            $table->setHeaders(['Snippet-Id', 'Name', 'Groups', 'File', 'Action']);
            while (true) {
                usleep(200000);
                $currentSnippets = $this->readDir($this->templateEngine->getTemplatePath(), $section1, false);
                ksort($currentSnippets);
                $update = false;
                $table->setRows([]);
                // removed or added?
                if (array_diff(array_keys($snippets), array_keys($currentSnippets))) {
                    $removed = $this->snippetsService->markAsRemovedWhereNotInList(array_keys($currentSnippets));
                    foreach ($removed as $snippet) {
                        $table->addRow([
                            $snippet->getId(),
                            $snippet->getName(),
                            implode(", ", $snippet->getGroups()),
                            $snippet->getFile(),
                            "<error>Removed</error>"
                        ]);
                        $update = true;
                    }
                }
                foreach ($currentSnippets as $snippet) {
                    if (! isset($snippets[$snippet["id"]]) ||
                        $snippets[$snippet["id"]]["mtime"] !== $snippet["mtime"] ||
                        $snippets[$snippet["id"]]["file"] !== $snippet["file"]) {
                        $changes = $this->snippetsService->insertOrUpdateSnippet($snippet["file"], $input->getOption("force"));
                        $table->addRow([
                            $snippet["id"],
                            $snippet["name"],
                            $snippet["groups"],
                            $snippet["file"],
                            count($changes) > 0 ? "<comment>" . implode(", ", $changes) . "</comment>" :
                                "<info>No changes</info>"
                        ]);
                        $update = true;
                    }
                }
                if ($update) {
                    $output->writeln(['', date('d.m.Y H:i:')]);
                    $table->render();
                    $this->templateEngine->generateSchema();
                }
                $snippets = $currentSnippets;
            }
        } else {
            $section1->writeln('Start Parsing');

            $snippets = $this->readDir($this->templateEngine->getTemplatePath(), $section1);
            ksort($snippets);
            $removed = $this->snippetsService->markAsRemovedWhereNotInList(array_keys($snippets));

            usleep(200000);
            $table = new Table($output);
            $table->setHeaders(['Snippet-Id', 'Name', 'Groups', 'File', 'Action']);
            foreach ($snippets as $snippet) {
                $changes = $this->snippetsService->insertOrUpdateSnippet($snippet["file"], $input->getOption("force"));
                $table->addRow([
                    $snippet["id"],
                    $snippet["name"],
                    $snippet["groups"],
                    $snippet["file"],
                    count($changes) > 0 ? "<comment>" . implode(", ", $changes) . "</comment>" :
                        "<info>No changes</info>"
                ]);
            }
            foreach ($removed as $snippet) {
                $table->addRow([
                    $snippet->getId(),
                    $snippet->getName(),
                    implode(", ", $snippet->getGroups()),
                    $snippet->getFile(),
                    "<error>Removed</error>"
                ]);
            }
            $table->render();
            $this->templateEngine->generateSchema();
        }
        return Command::SUCCESS;
    }

    /**
     * @param string               $dir
     * @param ConsoleSectionOutput $sectionOutput
     * @param bool                 $output
     * @param string[]|null        $snippets
     * @return string[]|null
     * @throws Exception
     */
    protected function readDir(string $dir, ConsoleSectionOutput $sectionOutput, bool $output = true, ?array &$snippets = null): ?array
    {
        if (! is_array($snippets)) {
            $snippets = [];
        }
        $handle = opendir($dir);
        if ($handle !== false) {
            while (false !== ($entry = readdir($handle))) {
                $entryPath = $dir . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($entryPath) && $entry !== "." && $entry !== "..") {
                    $this->readDir($entryPath, $sectionOutput, $output, $snippets);
                } else {
                    $path_parts = pathinfo($entryPath);
                    if ($path_parts['extension'] === "xml") {
                        if ($output) {
                            usleep(200000);
                            $sectionOutput->overwrite('Read Template: ' . $entryPath);
                        }
                        $metas = $this->snippetsService->getMetaDataFromTemplate($entryPath);
                        if (isset($snippets[$metas["id"]])) {
                            throw new Exception('Duplicated Snippet-Id ' . $metas["id"] . " (" . $snippets[$metas["id"]]["file"] . ")");
                        }
                        $snippets[$metas["id"]] = $metas;
                    }
                }
            }
        } else {
            throw new Exception('Can\'t read directory! ' . $dir);
        }
        return $snippets;
    }
}
