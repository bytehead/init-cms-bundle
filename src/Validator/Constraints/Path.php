<?php
/**
 * This file is part of the Networking package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class Path
 *
 * @Annotation
 *
 * @package Networking\InitCmsBundle\Validator\Constraints
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class Path extends Constraint
{
    public $message = 'This value is not a valid Path.';
}