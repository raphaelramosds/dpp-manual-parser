<?php

/**
 * Class for parsing text files with section and field definitions of a ANP report.
 */
class TxtParser 
{
    /**
     * @var string Report title.
     */
    private string $report;

    /**
     * @var array<string, string[]> List of sections and their respective fields.
     */
    private array $sections = [];

    /**
     * @var array<string, string> Rules associated with fields.
     */
    private array $fields_rules = [];

    /**
     * Constructor that loads and processes the given file.
     *
     * @param string $file Path to the text file.
     * @throws InvalidArgumentException If the file does not exist.
     * @throws RuntimeException If the file content is invalid.
     */
    public function __construct(string $file)
    {
        $this->loadFromFile($file);
    }

    /**
     * Loads and processes a text file.
     *
     * @param string $file Path to the file.
     */
    private function loadFromFile(string $file): void
    {
        if (!file_exists($file)) {
            throw new InvalidArgumentException("File not found: $file");
        }

        $content = file_get_contents($file);
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        if (empty($lines)) {
            throw new RuntimeException("Empty or invalid file.");
        }

        $this->report = array_shift($lines); // First line is the report title

        foreach ($lines as $line) {
            $this->parseEntry($line);
        }
    }

    /**
     * Processes a line from the file and extracts relevant information.
     *
     * @param string $entry Line to be processed.
     */
    private function parseEntry(string $entry): void
    {
        if (preg_match('/^FIELDS\s+OF\s+SECTION\s+"(.+)"\s+ARE\s+(.+);$/i', $entry, $matches)) {
            $fields = str_replace(' ', '', trim($matches[2]));
            $this->sections[$matches[1]] = explode(',', $fields);
        }

        if (preg_match('/^FIELD\s+"(.+)"\s+IS\s+(.+)/i', $entry, $matches)) {
            $this->fields_rules[$matches[1]] = trim($matches[2]);
        }
    }

    /**
     * Returns the report title.
     *
     * @return string
     */
    public function getReportTitle(): string
    {
        return $this->report;
    }

    /**
     * Returns all sections and their fields.
     *
     * @return array<string, string[]>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Returns the rules defined for the fields.
     *
     * @return array<string, string>
     */
    public function getFieldsRules(): array
    {
        return $this->fields_rules;
    }
}
