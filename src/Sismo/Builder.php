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


// @codeCoverageIgnoreStart
/**
 * Builds commits.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Aurelien Fontaine	<aurelien@efidev.com>
 */
interface Builder
{
  
    public function __construct($buildDir, $scmPath, array $scmCmds);

    public function init(Project $project, $callback = null);

    public function build();

    public function prepare($revision, $sync);

    //private function execute($command, $message);
}
// @codeCoverageIgnoreEnd
