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
     * @Route("/querywiki/{entityid}/{period}/{continue}", name="querywiki")
     */
    public function querywiki($entityid, $period, $continue)
    {

         $dates = explode("-", $period);
         $start = $dates[0]; //end, if &rvdir=older
         $end = $dates[1];  //start, if &rvdir=older
         $limit = 100;

         $process = new Process('python3.4 getrevisions.py '.$entityid.' '.$start.' '.$end.' '.$limit.' '.$continue);
         $process->run();

         // executes after the command finishes
         if (!$process->isSuccessful()) {
             throw new ProcessFailedException($process);
         }


           $response = new Response($process->getOutput());
           return $response;
      }

}
