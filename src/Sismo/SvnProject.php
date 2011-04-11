<?php

/*
 * This file is part of the Sismo utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sismo;

use Symfony\Component\Process\Process;

/**
 * Describes a project hosted on Github.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SvnProject extends Project
{

  public function getScm()
  {
    return 'svn';
  }

}
