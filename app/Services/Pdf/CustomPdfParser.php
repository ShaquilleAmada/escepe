<?php

namespace App\Services\Pdf;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Element\ElementNumeric;
use Smalot\PdfParser\Header;
use Smalot\PdfParser\Parser as BaseParser;
use Smalot\PdfParser\PDFObject;

class CustomPdfParser extends BaseParser
{
    public function __construct($cfg = [], ?Config $config = null)
    {
        parent::__construct($cfg, $config);
    }

    protected function parseObject(string $id, array $structure, ?Document $document)
    {
        $header = new Header([], $document);
        $content = '';

        foreach ($structure as $position => $part) {
            if (\is_int($part)) {
                $part = [null, null];
            }
            switch ($part[0]) {
                case '[':
                    $elements = [];

                    foreach ($part[1] as $sub_element) {
                        $sub_type = $sub_element[0];
                        $sub_value = $sub_element[1];
                        $elements[] = $this->parseHeaderElement($sub_type, $sub_value, $document);
                    }

                    $header = new Header($elements, $document);
                    break;

                case '<<':
                    $header = $this->parseHeader($part[1], $document);
                    break;

                case 'stream':
                    $content = isset($part[3][0]) ? $part[3][0] : $part[1];

                    if ($header->get('Type')->equals('ObjStm')) {
                        list($objectNumbers, $byteOffsets, $firstObjectOffset) = $this->customGetObjectStreamIndex($header, $content);
                        $content = substr($content, $firstObjectOffset);
                        $table = [];

                        foreach ($byteOffsets as $key => $byteOffset) {
                            $position = \strlen($byteOffset) < \strlen((string) \PHP_INT_MAX) ? (int) $byteOffset : \PHP_INT_MAX;
                            $table[$position] = $objectNumbers[$key];
                        }

                        ksort($table);

                        $ids = array_values($table);
                        $positions = array_keys($table);

                        foreach ($positions as $index => $position) {
                            $id = $ids[$index].'_0';
                            $next_position = isset($positions[$index + 1]) ? $positions[$index + 1] : \strlen($content);
                            $sub_content = substr($content, $position, (int) $next_position - (int) $position);

                            // Strip PDF comments in sub_content so Header::parse does not drop subsequent elements (Cairo PDF fix)
                            $sub_content = preg_replace('/%[^\r\n]*/', '', $sub_content);

                            $sub_header = Header::parse($sub_content, $document);
                            $object = PDFObject::factory($document, $sub_header, '', $this->getConfig());
                            $this->objects[$id] = $object;
                        }

                        return;
                    } elseif ($header->get('Type')->equals('Metadata')) {
                        $document->extractXMPMetadata($content);
                    }
                    break;

                default:
                    if ('null' != $part) {
                        $element = $this->parseHeaderElement($part[0], $part[1], $document);

                        if ($element) {
                            $header = new Header([$element], $document);
                        }
                    }
                    break;
            }
        }

        if (!isset($this->objects[$id])) {
            $this->objects[$id] = PDFObject::factory($document, $header, $content, $this->getConfig());
        }
    }

    private function customGetObjectStreamIndex(Header $header, string $content): array
    {
        $pattern = '/\G[\s\0]*(\d+)[\s\0]+(\d+)[\s\0]*/';
        preg_match_all($pattern, $content, $index);
        $length = \strlen(implode('', $index[0]));

        $first = $this->customGetObjectStreamInteger($header, 'First', $length - 1);

        if (
            null === $first
            || false === strpbrk(substr($content, $first, $length - $first), '0123456789')
            || (0 < $first && 2 === strspn($content, '0123456789', $first - 1, 2))
        ) {
            return [$index[1], $index[2], $length];
        }

        preg_match_all($pattern, substr($content, 0, $first), $indexInFrontOfFirst);
        $numberOfObjects = $this->customGetObjectStreamInteger($header, 'N', \strlen($content));

        if (
            $first !== \strlen(implode('', $indexInFrontOfFirst[0]))
            || (null !== $numberOfObjects && \count($indexInFrontOfFirst[0]) !== $numberOfObjects)
        ) {
            return [$index[1], $index[2], $length];
        }

        return [$indexInFrontOfFirst[1], $indexInFrontOfFirst[2], $first];
    }

    private function customGetObjectStreamInteger(Header $header, string $name, int $max): ?int
    {
        $element = $header->get($name);

        if (!$element instanceof ElementNumeric) {
            return null;
        }

        $value = $element->getContent();

        return 0 <= $value && $value <= $max ? (int) $value : null;
    }
}
