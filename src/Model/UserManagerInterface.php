<?php
/**
 * This file is part of the init_cms_sandbox package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Model;

/**
 * Class UserManagerInterface.
 *
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
interface UserManagerInterface
{
    /**
     * @return mixed
     */
    public function getLatestActivity();
}
