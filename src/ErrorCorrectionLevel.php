<?php
declare(strict_types=1);

namespace Simple\QrCode;
use BaconQrCode\Common\ErrorCorrectionLevel as BaconErrorCorrectionLevel;
use MyCLabs\Enum\Enum;

/**
 * @method static ErrorCorrectionLevel LOW()
 * @method static ErrorCorrectionLevel MEDIUM()
 * @method static ErrorCorrectionLevel QUARTILE()
 * @method static ErrorCorrectionLevel HIGH()
 */
class ErrorCorrectionLevel extends Enum
{
    const LOW = 'low';
    const MEDIUM = 'medium';
    const QUARTILE = 'quartile';
    const HIGH = 'high';

    public function toBaconErrorCorrectionLevel(): BaconErrorCorrectionLevel
    {
        $name = strtoupper(substr($this->getValue(), 0, 1));
        return BaconErrorCorrectionLevel::valueOf($name);
    }
}
