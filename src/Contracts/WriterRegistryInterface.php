<?php
declare(strict_types=1);

namespace Simple\QrCode\Contracts;

interface WriterRegistryInterface
{
    public function addWriters(iterable $writers): void;

    public function addWriter(WriterInterface $writer): void;

    public function getWriter(string $name): WriterInterface;

    public function getDefaultWriter(): WriterInterface;

    public function getWriters(): array;
}
