<?php
declare(strict_types=1);

namespace Simple\QrCode;

use Simple\QrCode\Contracts\WriterRegistryInterface;
use Simple\QrCode\Exception\InvalidException;
use Simple\QrCode\Writer\PngWriter;
use Simple\QrCode\Contracts\WriterInterface;

class WriterRegistry implements WriterRegistryInterface
{
    /** @var WriterInterface[] */
    private $writers = [];

    /** @var WriterInterface|null */
    private $defaultWriter;

    public function loadDefaultWriters(): void
    {
        if (count($this->writers) > 0) {
            return;
        }

        $this->addWriters([
            new PngWriter(),
        ]);

        $this->setDefaultWriter('png');
    }

    public function addWriters(iterable $writers): void
    {
        foreach ($writers as $writer) {
            $this->addWriter($writer);
        }
    }

    public function addWriter(WriterInterface $writer): void
    {
        $this->writers[$writer->getName()] = $writer;
    }

    public function getWriter(string $name): WriterInterface
    {
        $this->assertValidWriter($name);

        return $this->writers[$name];
    }

    public function getDefaultWriter(): WriterInterface
    {
        if ($this->defaultWriter instanceof WriterInterface) {
            return $this->defaultWriter;
        }

        throw new InvalidException('Please set the default writer via the second argument of addWriter');
    }

    public function setDefaultWriter(string $name): void
    {
        $this->defaultWriter = $this->writers[$name];
    }

    public function getWriters(): array
    {
        return $this->writers;
    }

    private function assertValidWriter(string $name): void
    {
        if (!isset($this->writers[$name])) {
            throw new InvalidException('Invalid writer "'.$name.'"');
        }
    }
}
