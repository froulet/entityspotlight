<?php
namespace AppBundle\Python;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
// use Symfony\Component\Form\FormBuilderInterface;
// use Doctrine\ORM\EntityManager;
// use AppBundle\Entity\Entity;
// use AppBundle\Entity\Revision;

class Python extends AbstractType
{
  protected $em;


   // public function __construct(EntityManager $em)
   // {
   //    $this->em = $em;
   // }


   public function entityParsing($entityid, $start, $end, $limit, $continue)
   {
         $process = new Process('python3.4 getrevisions.py '.$entityid.' '.$start.' '.$end.' '.$limit.' '.$continue);
         $process->run();

         // executes after the command finishes
         if (!$process->isSuccessful()) {
             throw new ProcessFailedException($process);
         }

           return $process->getOutput();
    }







   public function getName()
   {
       return 'filter_type';
   }

}

?>
