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
     * @Route("/entity", name="entity")
     */
    public function entityAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('entity.html.twig');
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

        list($pageid, $extract)=self::getDescription($slug);

        $continue = null;
        $revisions = array();

            // $entity = new Entity();
            // $entity->setIdEntity($pageid);
            // $entity->setTitle($slug);
            // $entity->setType("Unknow");
            // $entity->setDescription($extract);
            // $entity->setImglink("KEK");
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($entity);
            // $em->flush();

        do{
        list($revisions, $continue) = self::getRevisions($pageid, $continue, $revisions);
        }
        while($continue != null);

        foreach ($revisions as $key => $value) {
            echo "<br> <b> Changement le".$value[1]."<b>";

            //Créer une nouvelle révision ici
 
            // 
            foreach ($value[2] as $key2 => $categorytitle) {
                echo "<br>".$categorytitle;

            // $revision = new Revision();
            // $revision->setCategoryTitle($categorytitle);
            // $revision->setidEntity($pageid);
            // $revision->setidRevision($value[0]);
            // $date = new \DateTime($value[1]);
            // $revision->setDate($date);
            // //$revision->setDate(null);
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($revision);
            // $em->flush();

            

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

    public static function getRevisions($id, $continue, $revisions)
    {
        $url = "https://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=ids%7Ctimestamp%7Ccontent&format=json&pageids=".$id."&rvlimit=50&rvstart=20160101000000&rvend=20130101000000&rvdir=older";

        if($continue != null)
        {
            $url = $url."&rvcontinue=".$continue;
        }

           var_dump($url);

           $response = self::curl($url);
           

           //$lastPosts = $serializer->deserialize($response, null, 'json');
           $data = json_decode($response, true);


           reset($data["query"]["pages"]);
           $key = key($data["query"]["pages"]);


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
                     
                     echo "<b>".$val['revid']." - ".date($val['timestamp'])."</b><br>";

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


    public static function getDescription($slug){
     $url = "http://dbpedia.org/sparql?default-graph-uri=http%3A%2F%2Fdbpedia.org&query=select+distinct+%3Fid+%3Fabstract+where+%7B%0D%0A%0D%0A%3Chttp%3A%2F%2Fdbpedia.org%2Fresource%2F".$slug."%3E+%3Chttp%3A%2F%2Fdbpedia.org%2Fontology%2FwikiPageID%3E+%3Fid+.%0D%0A%3Chttp%3A%2F%2Fdbpedia.org%2Fresource%2F".$slug."%3E+%3Chttp%3A%2F%2Fdbpedia.org%2Fontology%2Fabstract%3E+%3Fabstract%0D%0A%0D%0AFILTER+langMatches%28lang%28%3Fabstract%29%2C%27en%27%29%0D%0A%7D+LIMIT+2&format=application%2Fsparql-results%2Bjson&timeout=30000";

        var_dump($url);

        $response = self::curl($url);

        $data = json_decode($response, true);

        $pageid= $data['results']['bindings'][0]['id']['value'];
        echo "<br><br>LE PAGE ID <br>".$pageid;
        $extract= $data['results']['bindings'][0]['abstract']['value'];
        echo "<br><br>LE EPIC EXTRACT <br>".$extract;

        $lereturn = array($pageid, $extract);

        return $lereturn;
    }

    
    public static function curl($url)
    {
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
           

           curl_close($ch);
           return $response;
    }

    public static function endKey($array){
    end($array);
    return key($array);
    }


}
