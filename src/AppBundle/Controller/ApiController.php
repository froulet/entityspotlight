<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use AppBundle\Entity\Entity;
use AppBundle\Entity\Revision;
use AppBundle\Controller\DefaultController;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ApiController extends Controller
{


    /**
     * @Route("/getrevision/{revid}", name="getRevision")
     */
    public function getRevisionAction($revid)
    {
        $revision = $this->getDoctrine()
        ->getRepository('AppBundle:Revision')
        ->findByidRevision($revid);

        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

        $response = new Response($serializer->serialize($revision, 'json'));

        return $response;
    }


    /**
     * @Route("/addentity/{entityid}", name="addEntity")
     */
    public function addEntityAction($entityid)
    {

        DefaultController::createEntity($entityid);
        $response = new Response("OK !");

        return $response;
    }



    /**
     * @Route("/getlistrevisions/{entityid}", name="getListRevisions")
     */
    public function getListRevisionsAction($entityid)
    {
        $revisions = $this->getDoctrine()
        ->getRepository('AppBundle:Revision')
        ->findByidEntity($entityid);

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
                'SELECT p.idRevision, p.date
                FROM AppBundle:Revision p
                GROUP BY p.idRevision'
            );

        $revisions = $query->getResult();



        $response = new Response($serializer->serialize($revisions, 'json'));

        return $response;
    }

        /**
     * @Route("/querywiki/{entityname}/{period}/{continue}", name="querywiki")
     */
    public function querywiki($entityname, $period, $continue)
    {

         $dates = explode("-", $period);
         $start = $dates[0]; //end, if &rvdir=older
         $end = $dates[1];  //start, if &rvdir=older
         $limit = 100;

        $controller = $this->get('python');
        $result = $controller->entityParsing($entityname, $start, $end, $limit, $continue);


           $response = new Response($result);
           return $response;
      }



    /**
     * @Route("/bulkimport/{entityname}/{period}/{continue}", name="bulkImport")
     */
    public function bulkImport($entityname, $period, $continue)
    {
         $entityid=DefaultController::createRevision($entityname);
         $dates = explode("-", $period);
         $start = $dates[0]; //end, if &rvdir=older
         $end = $dates[1];  //start, if &rvdir=older
         $limit = 500;

         //We call the Service 'python'
          $controller = $this->get('python');
          $result = $controller->entityParsing($entityname, $start, $end, $limit, $continue);

          $data = json_decode($result, true);

          //We call the Service 'databasemanager'
          $database = $this->get('databasemanager');
           
           foreach ($data['revisions'] as $key => $value) {
            //var_dump($value);

                if(isset($value['categories']))
                {
                    foreach ($value['categories'] as $kay => $val) {
                        echo "LA CATE :".$val."<br>";
                        $database->createRevision($val, $entityid, $value['revid'], $value['timestamp']);
                    }
                }
          
            }

          return new Response("D'accord.");

      }


}
