<?php
declare(strict_types=1);

namespace Simple\QrCode;
use MyCLabs\Enum\Enum;
/**
 * @method static LabelAlignment LEFT()
 * @method static LabelAlignment CENTER()
 * @method static LabelAlignment RIGHT()
 */
class LabelAlignment extends Enum
{
    const LEFT = 'left';
    const CENTER = 'center';
    const RIGHT = 'right';
}
