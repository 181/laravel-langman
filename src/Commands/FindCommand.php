<?php

namespace Themsaid\Langman\Commands;

use Illuminate\Console\Command;
use Themsaid\Langman\Manager;
use Illuminate\Support\Str;

class FindCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'langman:find {keyword}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $description = 'Find key with values matching the keyword.';

    /**
     * The Languages manager instance.
     *
     * @var \Themsaid\LangMan\Manager
     */
    private $manager;

    /**
     * Array of files grouped by filename.
     *
     * @var array
     */
    protected $files;

    /**
     * ListCommand constructor.
     *
     * @param \Themsaid\LangMan\Manager $manager
     * @return void
     */
    public function __construct(Manager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->files = $this->manager->files();

        if (empty($this->files)) {
            $this->warn('No language files were found!');
        }

        $languages = $this->manager->languages();

        $this->table(
            array_merge(['key'], $languages),
            $this->tableRows()
        );
    }

    /**
     * The output of the table rows.
     *
     * @return array
     */
    private function tableRows() : array
    {
        $allLanguages = $this->manager->languages();

        $filesContent = [];

        $output = [];

        foreach ($this->files as $fileName => $fileLanguages) {
            foreach ($fileLanguages as $languageKey => $filePath) {
                $lines = $filesContent[$languageKey] = $this->manager->getFileContent($filePath);

                foreach ($lines as $key => $line) {
                    if (! is_array($line) && Str::contains($line, $this->argument('keyword'))) {
                        $output[$key]['key'] = $fileName.'.'.$key;
                        $output[$key][$languageKey] = "<bg=yellow;fg=black>{$line}</>";
                    }
                }
            }
        }

        // Now that we collected all values that matches the keyword argument
        // in a close match, we collect the values for the rest of the
        // languages for the found keys to complete the table view.
        foreach ($output as $key => $values) {
            $original = [];

            foreach ($allLanguages as $languageKey) {
                $original[$languageKey] = $values[$languageKey] ?? $filesContent[$languageKey][$key] ?? '';
            }

            // Sort the language values based on language name
            ksort($original);

            $output[$key] = array_merge(['key' => $key], $original);
        }

        return array_values($output);
    }
}