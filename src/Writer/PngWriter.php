<?php
declare(strict_types=1);

namespace Simple\QrCode\Writer;

use Simple\QrCode\Exception\GenerateImageException;
use Simple\QrCode\Exception\MissingException;
use Simple\QrCode\Exception\ValidationException;
use Simple\QrCode\LabelAlignment;
use Simple\QrCode\Contracts\QrCodeInterface;
use Simple\QrCode\Tools\Helper;
use Zxing\QrReader;

class PngWriter extends AbstractWriter
{
    use Helper;
    private $step = 0;
    public function writeString(QrCodeInterface $qrCode): string
    {
        $image = $this->createImage($qrCode->getData(), $qrCode);

        $logoPath = $qrCode->getLogoPath();
        if (null !== $logoPath) {
            $image = $this->addLogo($image, $logoPath, $qrCode->getLogoWidth(), $qrCode->getLogoHeight());
        }

        $label = $qrCode->getLabel();
        if (null !== $label) {
            $image = $this->addLabel($image, $label, $qrCode->getLabelFontPath(), $qrCode->getLabelFontSize(), $qrCode->getLabelAlignment(), $qrCode->getLabelMargin(), $qrCode->getForegroundColor(), $qrCode->getBackgroundColor());
        }

        $string = $this->imageToString($image);

        imagedestroy($image);

        if ($qrCode->getValidateResult()) {
            $reader = new QrReader($string, QrReader::SOURCE_TYPE_BLOB);
            if ($reader->text() !== $qrCode->getText()) {
                throw new ValidationException('Built-in validation reader read "'.$reader->text().'" instead of "'.$qrCode->getText().'".
                     Adjust your parameters to increase readability or disable built-in validation.');
            }
        }

        return $string;
    }

    private function createImage(array $data, QrCodeInterface $qrCode)
    {
        $baseSize = $qrCode->getRoundBlockSize() ? $data['block_size'] : 25;

        $baseImage = $this->createBaseImage($baseSize, $data, $qrCode);
        $interpolatedImage = $this->createInterpolatedImage($baseImage, $data, $qrCode);

        imagedestroy($baseImage);

        return $interpolatedImage;
    }

    private function createBaseImage(int $baseSize, array $data, QrCodeInterface $qrCode)
    {
        $image = imagecreatetruecolor($data['block_count'] * $baseSize, $data['block_count'] * $baseSize);

        if (!is_resource($image)) {
            throw new GenerateImageException('Unable to generate image: check your GD installation');
        }

        $foregroundColor = imagecolorallocatealpha($image, $qrCode->getForegroundColor()['r'], $qrCode->getForegroundColor()['g'], $qrCode->getForegroundColor()['b'], $qrCode->getForegroundColor()['a']);
        $backgroundColor = imagecolorallocatealpha($image, $qrCode->getBackgroundColor()['r'], $qrCode->getBackgroundColor()['g'], $qrCode->getBackgroundColor()['b'], $qrCode->getBackgroundColor()['a']);
        imagefill($image, 0, 0, $backgroundColor);

        if ($qrCode->getForegroundColorJb()) {
            $colors = $this->cutFill($image, $data['inner_height'], $data['inner_width'], $qrCode->getForegroundColorJb()['start'], $qrCode->getForegroundColorJb()['end'], $qrCode->getGradientType(), $data['block_size']);
        }

        foreach ($data['matrix'] as $row => $values) {
            foreach ($values as $column => $value) {
                if (1 === $value) {
                    if (isset($colors) && !empty($colors)) {
                        switch($qrCode->getGradientType()) {
                            case 'vertical':
                                imagefilledrectangle($image, $column * $baseSize, $row * $baseSize, ($column + 1) * $baseSize, ($row + 1) * $baseSize, $colors[$row]);
                                break;
                            case 'horizontal':
                                imagefilledrectangle( $image, $row * $baseSize, $column * $baseSize, intval(($row + 1) * $baseSize),  intval(($column + 1) * $baseSize), $colors[$row] );
                                break;
/*
 * 余下这几种类型还需重新计算
                         case 'ellipse':
                            case 'ellipse2':
                            case 'circle':
                            case 'circle2':
                                imagefilledellipse ($image,$data['inner_width']/2, $data['inner_height']/2, ($data['inner_height']-$i)*$rh, ($line_numbers-$i)*$rw, $colors[$row]);
                                break;
                            case 'square':
                            case 'rectangle':
                                imagefilledrectangle ($image, $row* $data['inner_width']/$data['inner_height'],$row*$data['inner_height']/ $data['inner_width'], $data['inner_width']-($row* $data['inner_width']/$data['inner_height']), $data['inner_height']-($row*$data['inner_height']/ $data['inner_width']), $colors[$row]);
                                break;
                            case 'diamond':
                                imagefilledpolygon($image, array (
                                    $width/2, $i*$rw-0.5*$height,
                                    $i*$rh-0.5*$width, $height/2,
                                    $width/2,1.5*$height-$i*$rw,
                                    1.5*$width-$i*$rh, $height/2 ), 4, $colors[$row]);
                                break;
*/                          default:
                        }

                    } else {
                        imagefilledrectangle($image, $column * $baseSize, $row * $baseSize, intval(($column + 1) * $baseSize), intval(($row + 1) * $baseSize), $foregroundColor);
                    }
                }
            }
        }

        return $image;
    }

    private function createInterpolatedImage($baseImage, array $data, QrCodeInterface $qrCode)
    {
        $image = imagecreatetruecolor($data['outer_width'], $data['outer_height']);

        if (!is_resource($image)) {
            throw new GenerateImageException('Unable to generate image: check your GD installation');
        }

        $backgroundColor = imagecolorallocatealpha($image, $qrCode->getBackgroundColor()['r'], $qrCode->getBackgroundColor()['g'], $qrCode->getBackgroundColor()['b'], $qrCode->getBackgroundColor()['a']);
        imagefill($image, 0, 0, $backgroundColor);
        imagecopyresampled($image, $baseImage, (int) $data['margin_left'], (int) $data['margin_left'], 0, 0, (int) $data['inner_width'], (int) $data['inner_height'], imagesx($baseImage), imagesy($baseImage));
        imagesavealpha($image, true);

        return $image;
    }

    private function addLogo($sourceImage, string $logoPath, int $logoWidth = null, int $logoHeight = null)
    {
        $mimeType = $this->getMimeType($logoPath);
        $logoImage = imagecreatefromstring(strval(file_get_contents($logoPath)));

        if ('image/svg+xml' === $mimeType && (null === $logoHeight || null === $logoWidth)) {
            throw new MissingException('SVG Logos require an explicit height set via setLogoSize($width, $height)');
        }

        if (!is_resource($logoImage)) {
            throw new GenerateImageException('Unable to generate image: check your GD installation or logo path');
        }

        $logoSourceWidth = imagesx($logoImage);
        $logoSourceHeight = imagesy($logoImage);

        if (null === $logoWidth) {
            $logoWidth = $logoSourceWidth;
        }

        if (null === $logoHeight) {
            $aspectRatio = $logoWidth / $logoSourceWidth;
            $logoHeight = intval($logoSourceHeight * $aspectRatio);
        }

        $logoX = imagesx($sourceImage) / 2 - $logoWidth / 2;
        $logoY = imagesy($sourceImage) / 2 - $logoHeight / 2;

        imagecopyresampled($sourceImage, $logoImage, intval($logoX), intval($logoY), 0, 0, $logoWidth, $logoHeight, $logoSourceWidth, $logoSourceHeight);

        imagedestroy($logoImage);

        return $sourceImage;
    }

    private function addLabel($sourceImage, string $label, string $labelFontPath, int $labelFontSize, string $labelAlignment, array $labelMargin, array $foregroundColor, array $backgroundColor)
    {
        if (!function_exists('imagettfbbox')) {
            throw new MissingException('Missing function "imagettfbbox", please make sure you installed the FreeType library');
        }

        $labelBox = imagettfbbox($labelFontSize, 0, $labelFontPath, $label);
        $labelBoxWidth = intval($labelBox[2] - $labelBox[0]);
        $labelBoxHeight = intval($labelBox[0] - $labelBox[7]);

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        $targetWidth = $sourceWidth;
        $targetHeight = $sourceHeight + $labelBoxHeight + $labelMargin['t'] + $labelMargin['b'];

        // Create empty target image
        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        if (!is_resource($targetImage)) {
            throw new GenerateImageException('Unable to generate image: check your GD installation');
        }

        $foregroundColor = imagecolorallocate($targetImage, $foregroundColor['r'], $foregroundColor['g'], $foregroundColor['b']);
        $backgroundColor = imagecolorallocate($targetImage, $backgroundColor['r'], $backgroundColor['g'], $backgroundColor['b']);
        imagefill($targetImage, 0, 0, $backgroundColor);

        // Copy source image to target image
        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $sourceWidth, $sourceHeight, $sourceWidth, $sourceHeight);

        imagedestroy($sourceImage);

        switch ($labelAlignment) {
            case LabelAlignment::LEFT:
                $labelX = $labelMargin['l'];
                break;
            case LabelAlignment::RIGHT:
                $labelX = $targetWidth - $labelBoxWidth - $labelMargin['r'];
                break;
            default:
                $labelX = intval($targetWidth / 2 - $labelBoxWidth / 2);
                break;
        }

        $labelY = $targetHeight - $labelMargin['b'];
        imagettftext($targetImage, $labelFontSize, 0, $labelX, $labelY, $foregroundColor, $labelFontPath, $label);

        return $targetImage;
    }

    private function imageToString($image): string
    {
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    public static function getContentType(): string
    {
        return 'image/png';
    }

    public static function getSupportedExtensions(): array
    {
        return ['png'];
    }

    public function getName(): string
    {
        return 'png';
    }

    // The main function that draws the gradient
    private function cutFill($im, $line_numbers, $line_width, $start, $end, $direction, $step = 0) {
        if ($step) $this->step = $step;
        list($r1,$g1,$b1) = self::hex2rgb($start);
        list($r2,$g2,$b2) = self::hex2rgb($end);

/*
        switch($direction) {
            case 'horizontal':
                $line_numbers = imagesx($im);
                $line_width = imagesy($im);
                break;
            case 'vertical':
                $line_numbers = imagesy($im);
                $line_width = imagesx($im);
                break;
            case 'ellipse':
                $width = imagesx($im);
                $height = imagesy($im);
                $rh=$height>$width?1:$width/$height;
                $rw=$width>$height?1:$height/$width;
                $line_numbers = min($width,$height);
                $center_x = $width/2;
                $center_y = $height/2;
                list($r1,$g1,$b1) = $this->hex2rgb($end);
                list($r2,$g2,$b2) = $this->hex2rgb($start);
                imagefill($im, 0, 0, imagecolorallocate( $im, $r1, $g1, $b1 ));
                break;
            case 'ellipse2':
                $width = imagesx($im);
                $height = imagesy($im);
                $rh=$height>$width?1:$width/$height;
                $rw=$width>$height?1:$height/$width;
                $line_numbers = sqrt(pow($width,2)+pow($height,2));
                $center_x = $width/2;
                $center_y = $height/2;
                list($r1,$g1,$b1) = $this->hex2rgb($end);
                list($r2,$g2,$b2) = $this->hex2rgb($start);
                break;
            case 'circle':
                $width = imagesx($im);
                $height = imagesy($im);
                $line_numbers = sqrt(pow($width,2)+pow($height,2));
                $center_x = $width/2;
                $center_y = $height/2;
                $rh = $rw = 1;
                list($r1,$g1,$b1) = $this->hex2rgb($end);
                list($r2,$g2,$b2) = $this->hex2rgb($start);
                break;
            case 'circle2':
                $width = imagesx($im);
                $height = imagesy($im);
                $line_numbers = min($width,$height);
                $center_x = $width/2;
                $center_y = $height/2;
                $rh = $rw = 1;
                list($r1,$g1,$b1) = $this->hex2rgb($end);
                list($r2,$g2,$b2) = $this->hex2rgb($start);
                imagefill($im, 0, 0, imagecolorallocate( $im, $r1, $g1, $b1 ));
                break;
            case 'square':
            case 'rectangle':
                $width = imagesx($im);
                $height = imagesy($im);
                $line_numbers = max($width,$height)/2;
                list($r1,$g1,$b1) = $this->hex2rgb($end);
                list($r2,$g2,$b2) = $this->hex2rgb($start);
                break;
            case 'diamond':
                list($r1,$g1,$b1) = $this->hex2rgb($end);
                list($r2,$g2,$b2) = $this->hex2rgb($start);
                $width = imagesx($im);
                $height = imagesy($im);
                $rh=$height>$width?1:$width/$height;
                $rw=$width>$height?1:$height/$width;
                $line_numbers = min($width,$height);
                break;
            default:
        }
*/

        $r = $g = $b = 255;
        $colors = [];
        $fill = '';
        for ( $i = 0; $i < $line_numbers; $i=$i+$this->step ) {
            // old values :
            $old_r=$r;
            $old_g=$g;
            $old_b=$b;
            // new values :
            $r = ( $r2 - $r1 != 0 ) ? intval( $r1 + ( $r2 - $r1 ) * ( $i / $line_numbers ) ): $r1;
            $g = ( $g2 - $g1 != 0 ) ? intval( $g1 + ( $g2 - $g1 ) * ( $i / $line_numbers ) ): $g1;
            $b = ( $b2 - $b1 != 0 ) ? intval( $b1 + ( $b2 - $b1 ) * ( $i / $line_numbers ) ): $b1;


            if ( "$old_r,$old_g,$old_b" != "$r,$g,$b")
                $fill = imagecolorallocate( $im, $r, $g, $b );
            $colors[] = $fill;
        }
        return $colors;
    }

}
