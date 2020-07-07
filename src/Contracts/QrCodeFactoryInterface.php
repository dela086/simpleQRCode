<?php
declare(strict_types=1);

namespace Simple\QrCode\Contracts;

interface QrCodeFactoryInterface
{
    public function create(string $text = '', array $options = []): QrCodeInterface;
}
