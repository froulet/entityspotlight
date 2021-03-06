<?php
namespace AppBundle\DataBase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Entity;
use AppBundle\Entity\Revision;

class Queries extends AbstractType
{
  protected $em;

  /**
  * @InjectParams({
  *    "em" = @Inject("doctrine.orm.entity_manager")
  * })
  */
   public function __construct(EntityManager $em)
   {
      $this->em = $em;
   }

   public function buildForm(FormBuilderInterface $builder, array $options)
   {
     // Do something with your Entity Manager using "$this->em"
   }

   public function createEntity($pageid, $slug, $type, $extract, $thumbnail)
   {
     $entity = $this->em->getRepository('AppBundle:Entity')
     ->find($pageid);

     //Si l'entité n'existe pas déjà, on la crée
     if (!$entity) {
       $entity = new Entity();
     }

     $entity->setIdEntity($pageid);
     $entity->setTitle($slug);
     $entity->setType($type);
     $entity->setDescription($extract);
     $entity->setImglink($thumbnail);

     $this->em->persist($entity);
     $this->em->flush();
}




public function createRevision($categoryTitle, $idEntity, $idRevision, $date)
{
  $revision = $this->em->getRepository('AppBundle:Revision')
  ->findBy(array('idRevision' => $idRevision, 'categoryTitle' => $categoryTitle ));

  //Si l'entité n'existe pas déjà, on la crée
  if (!$revision) {
    //echo "Nouvelle Révision";
    $revision = new Revision();
  }

  else
  {
    //echo "Already existing revision.";
    $revision = $revision[0];
  }

  $revision->setCategoryTitle($categoryTitle);
  $revision->setidEntity($idEntity);
  $revision->setidRevision($idRevision);
  $date = new \DateTime($date);
  $revision->setDate($date);

  //Persist the revision
  $this->em->persist($revision);
  $this->em->flush();
}


public function saveRevisions($result, $entityname)
{
            $data = json_decode($result, true);
            $entityid = $this->getEntityIdByName($entityname);

           foreach ($data['revisions'] as $key => $value) {
            //var_dump($value);

                if(isset($value['categories']))
                {
                    foreach ($value['categories'] as $kay => $val) {
                        $this->createRevision($val, $entityid, $value['revid'], $value['timestamp']);
                    }
                }
          
            }
}


public function getEntityIdByName($entitytitle)
{

 $entity = $this->em->getRepository('AppBundle:Entity')
  ->findOneBy(array('title' => $entitytitle));

  return $entity->getIdEntity();

}

public function getLastRevision($categoryTitle, $idEntity, $idRevision, $date)
{
  $revision = $this->em->getRepository('AppBundle:Revision')
  ->findBy(array('idRevision' => $idRevision, 'idEntity' => $categoryTitle ));

}




   public function getName()
   {
       return 'filter_type';
   }

}

?>
