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

        $encoders = array(new XmlEncoder(), new JsonEncoder());
        $normalizers = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizers, $encoders);

         $dates = explode("-", $period);
         $start = $dates[0]; //end, if &rvdir=older
         $end = $dates[1];  //start, if &rvdir=older 


         $url = "https://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=ids%7Ctimestamp%7Cuser%7Cuserid%7Ctags%7Cflags%7Ccontent%7Csize&format=json&pageids=".$entityid."&rvlimit=50&rvstart=".$start."&rvend=".$end."&rvdir=older";

         $url = DefaultController::addContinue($continue, $url);

         $response = DefaultController::curl($url);
         $data = json_decode($response, true);


         $cat = DefaultController::get1Revisions($data['query']['pages']);

         
         $cont = array();
         $cont['continue'] = DefaultController::checkContinue($data);

         $gen = array();
         $gen['revisions'] = $cat;
         $gen['continue'] = $cont;
         

        $response = new Response($serializer->serialize($gen , 'json'));
        return $response;
    }




}
