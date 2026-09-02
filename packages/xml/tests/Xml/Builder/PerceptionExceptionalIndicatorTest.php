<?php

declare(strict_types=1);

namespace Tests\Greenter\Xml\Builder;

use DOMDocument;
use DOMElement;
use Greenter\Data\Generator\PerceptionStore;
use Greenter\Data\SharedStore;
use Greenter\Model\Perception\Perception;
use Greenter\Xml\Builder\PerceptionBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Field 7 of SUNAT's "Percepciones1_0" validation sheet:
 * /Perception/sac:ExceptionalIndicator
 *
 * It is a conditional field (an2). Its only accepted value is '01' (rule
 * 3322); when present, the document may reference a single related document
 * (3323) and that document must be an invoice (3324).
 */
class PerceptionExceptionalIndicatorTest extends TestCase
{
    public function testIsOmittedWhenNotSet(): void
    {
        $xml = $this->build(null);

        self::assertStringNotContainsString('ExceptionalIndicator', $xml);
    }

    public function testIsWrittenWithItsValue(): void
    {
        $xml = $this->build('01');

        self::assertStringContainsString(
            '<sac:ExceptionalIndicator>01</sac:ExceptionalIndicator>',
            $xml
        );
    }

    /**
     * The sheet numbers the fields in document order: 6 is cbc:IssueTime and
     * 8 is the agent's identity document, so field 7 belongs between them.
     */
    public function testIsPlacedBetweenIssueTimeAndAgentParty(): void
    {
        $names = $this->childNames($this->build('01'));

        $indicator = array_search('sac:ExceptionalIndicator', $names, true);
        self::assertNotFalse($indicator);

        self::assertGreaterThan(array_search('cbc:IssueTime', $names, true), $indicator);
        self::assertLessThan(array_search('cac:AgentParty', $names, true), $indicator);
    }

    private function build(?string $indicator): string
    {
        /** @var Perception $perception */
        $perception = (new PerceptionStore(new SharedStore()))->create();
        $perception->setExceptionalIndicator($indicator);

        return (new PerceptionBuilder())->build($perception);
    }

    /**
     * @return string[]
     */
    private function childNames(string $xml): array
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);

        $names = [];
        foreach ($doc->documentElement->childNodes as $node) {
            if ($node instanceof DOMElement) {
                $names[] = $node->nodeName;
            }
        }

        return $names;
    }
}
