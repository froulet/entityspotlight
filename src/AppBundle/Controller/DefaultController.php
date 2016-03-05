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


class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('home.html.twig');
    }

        /**
     * @Route("/installdone", name="installed")
     */
    public function installedAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }


    /**
     * @Route("/parse/entities/{slug}")
     */
    public function getDeltas($slug)
    {

        //$encoders = array(new XmlEncoder(), new JsonEncoder());
        //$normalizers = array(new ObjectNormalizer());

        //$serializer = new Serializer($normalizers, $encoders);

        $continue = null;
        $revisions = array();

            $entity = new Entity();
            $entity->setTitle($slug);
            $entity->setType("Unknow");
            $entity->setDescription("Unknow");
            $entity->setImglink("KEK");
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();


        do{
        list($revisions, $continue) = self::getRevisions($slug, $continue, $revisions);
        }
        while($continue != null);

        foreach ($revisions as $key => $value) {
            echo "<br> <b> Changement le".$value[1]."<b>";

            foreach ($value[2] as $key2 => $value2) {
                echo "<br>".$value2;
            }
        }

            echo "<br>";

               return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
   
}


    public static function identical_values( $arrayA , $arrayB ) { 

        sort( $arrayA ); 
        sort( $arrayB ); 

        return $arrayA == $arrayB; 
    } 

    public static function getRevisions($slug, $continue, $revisions)
    {
        $url = "https://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=ids%7Ctimestamp%7Ccontent&format=json&titles=".$slug."&rvlimit=50&rvstart=20160101000000&rvend=20130101000000&rvdir=older";

        if($continue != null)
        {
            $url = $url."&rvcontinue=".$continue;
        }

        var_dump($url);

        // is curl installed?
        if (!function_exists('curl_init')){ 
              die('CURL is not installed!');
        }
           
           // get curl handle
           $ch= curl_init();

           // set request url
           curl_setopt($ch, 
              CURLOPT_URL, 
              $url);

           // return response, don't print/echo
           curl_setopt($ch, 
              CURLOPT_RETURNTRANSFER, 
              true);
         
           /*
           Here you find more options for curl:
           http://www.php.net/curl_setopt
           */    

           $response = curl_exec($ch);
           
           //var_dump($response);

           curl_close($ch);

           //$lastPosts = $serializer->deserialize($response, null, 'json');
           $data = json_decode($response, true);


           reset($data["query"]["pages"]);
           $key = key($data["query"]["pages"]);

           $pageid= $data["query"]["pages"][$key]["pageid"];
           $title = $data["query"]["pages"][$key]["title"];
            echo "<b>".$title." - Id page : ".$pageid."</b><br>";


           $continue = (isset($data["continue"]["rvcontinue"])) ? $data["continue"]["rvcontinue"] : null;

          $categories = array();
          $healthy = array("| ", "|", " ");
          $yummy   = array("", "","_");

           foreach ($data["query"]["pages"][$key]["revisions"] as $key => $val) {

               $regex = "/\[\[Category:[^?*-@#]*?\]\]/";

               $newcategories = array();

                //if (($tmp = strstr($val["*"], '{{Reflist')) !== false) {
                //     $str = substr($tmp, 1);
                //}

               if (preg_match_all($regex, $val["*"], $matches_out)) {
                     
                     echo "<b>".$val['revid']." - ".$val['timestamp']."</b><br>";

                     foreach ($matches_out[0]as $key => $value) {
                         $str = substr($value, 11, -2);
                         $str = str_replace($healthy, $yummy, $str);
                         //echo $str."<br>";
                         $newcategories[] = $str;
                         }

                         

                         $tkey = self::endKey($revisions);

                         $rev = [$val['revid'], $val['timestamp'], $newcategories];

                    if (!empty($revisions)) {
                           $diff = array_diff($revisions[$tkey][2], $newcategories);

                            if (sizeof($diff) > 0) 
                         //if (!self::identical_values($revisions[$tkey][2], $newcategories)) 
                         {
                             echo "<br>CHANGEMENT ICI !!<br>";
                             $revisions[] = $rev;
                         }
                       }

                       else
                       {
                        $revisions[] = $rev;
                       }                      
                         
                    }
                }

                var_dump($revisions);
                $lereturn = array($revisions, $continue);
                return $lereturn;
    }

    public static function endKey($array){
    end($array);
    return key($array);
    }


}
