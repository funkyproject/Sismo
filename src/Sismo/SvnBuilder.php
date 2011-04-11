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
use Symfony\Component\HttpKernel\Util\Filesystem;

// @codeCoverageIgnoreStart
/**
 * Builds commits svn.
 *
 * @author Aurelien Fontaine <aurelien@efidev.com>
 */
class SvnBuilder implements Builder
{
  private $project;
  private $baseBuildDir;
  private $buildDir;
  private $callback;
  private $svnPath;
  private $scmCmds;

  public function __construct($buildDir, $scmPath, array $scmCmds)
  {
    $this->baseBuildDir = $buildDir;
    $this->scmPath = $scmPath;
    $this->scmCmds = array_replace(array(
            'checkout'    => 'checkout %repo%/%branch% %dir%',
            'update' => 'update -r %revision%',
    				'log'     => 'log -r %revision%',
    ), $scmCmds);
  }

  public function init(Project $project, $callback = null)
  {
    $this->project  = $project;
    $this->callback = $callback;
    $this->buildDir = $this->baseBuildDir.'/'.substr(md5($project->getRepository()), 0, 6);
  }

  public function build()
  {
    file_put_contents($this->buildDir.'/sismo-run-tests.sh', str_replace(array("\r\n", "\r"), "\n", $this->project->getCommand()));

    $process = new Process('sh sismo-run-tests.sh', $this->buildDir);
    $process->setTimeout(3600);
    $process->run($this->callback);
    return $process;
  }

  public function prepare($revision, $sync)
  {

    if (!file_exists($this->buildDir)) {
      $filesystem = new Filesystem();
      $filesystem->mkdir($this->buildDir);
    }

    if (!file_exists($this->buildDir.'/.svn')) {
      $this->execute(strtr($this->scmPath.' '.$this->scmCmds['clone'], array('%repo%' => $this->project->getRepository(), '%dir%' => $this->buildDir , '%branch%' => $this->project->getBranch())), sprintf('Unable to checkout repository for project "%s".', $this->project));
    }

    if ($sync) {
      $this->execute(strtr($this->scmPath.' '.$this->scmCmds['update'], array('%revision%' => (null === $revision)?'head':$revision)), sprintf('Unable to update repository for project "%s".', $this->project));
    }

    if (null === $revision || 'HEAD' === $revision) {
      $revision = null;
      if (file_exists($file = $this->buildDir.'/.svn/entries')) {
        $lignes  = explode("\n",trim(file_get_contents($file)));
        $revision = (int) $lignes[3];
      }
        

      if (null === $revision) {
        throw new BuildException(sprintf('Unable to get HEAD for branch "%s" for project "%s".', $this->project->getBranch(), $this->project));
      }
    }

    $process = $this->execute(strtr($this->scmPath.' '.$this->scmCmds['log'], array('%revision%' => $revision)), sprintf('Unable to get logs for project "%s".', $this->project));

    $lignes =explode("\n", $process->getOutput());
    
    if(isset($lignes[2]))
    {
      list($revision, $author,  $date, $countLine) = explode("|",$lignes[1]);

      $message = '';      
      $date = substr($date, 1, 25 );
      $numberLine  = (int) substr($countLine, 1 ,1) + 3;
      
      for($i=3; $i< $numberLine; $i++ )
      {
        $message .= $lignes[$i]."\n";
      }
      
    }
    else 
    {
      $author = "anonymous";
      $date = date('Y-m-d H:i:s O');
      $message = 'Force commit : no message !! it\'s evil'; 
    }
    
    return array(
      str_replace('r','',$revision),  
      $author,
      $date,
      $message
    );
  }

  private function execute($command, $message)
  {
    if (null !== $this->callback) {
      call_user_func($this->callback, 'out', sprintf("Running \"%s\"\n", $command));
    }

    $process = new Process($command, $this->buildDir);
    $process->setTimeout(3600);
    $process->run($this->callback);
    if ($process->getExitCode() > 0) {
      throw new BuildException($message);
    }

    return $process;
  }
}
// @codeCoverageIgnoreEnd
