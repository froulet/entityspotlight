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
use AppBundle\Form\EntityImport;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
  * @Route("/search/", name="research")
  */
  public function entityAction(Request $request)
  {


    $tq=$request->query->get('query');
    $error = null;

    if($tq == '')
    {
        //throw new NotFoundHttpException("Page not found");
        $error = "Query is empty !";
        return $this->render('error.html.twig', array('error'=>$error));
    }
    //echo "LA QUERY <br>".$tq;
    $entity = $this->getDoctrine()
    ->getRepository('AppBundle:Entity')
    ->findByTitle($tq);


      $em = $this->getDoctrine()->getManager();
      $query = $em->getRepository("AppBundle:Entity")->createQueryBuilder('e')
      ->where('e.title LIKE :title')
      ->setParameter('title', '%'.$tq.'%')
      ->getQuery()
      ->getResult();


      $paginator  = $this->get('knp_paginator');
      $pagination = $paginator->paginate(
      $query, /* query NOT result */
      $request->query->getInt('page', 1)/*page number*/, 10/*limit per page*/);

    //var_dump($pagination);

    return $this->render('search.html.twig', array('pagination' => $pagination, 'error'=>$error));
  
}

  /**
  * @Route("/listentowikipedia", name="listentowikipedia")
  */
  public function l2w(Request $request)
  {
    // replace this example code with whatever you need
    return $this->render('l2w.html.twig');
  }



  /**
  * @Route("/manage", name="manageentities")
  */
public function manageEntitiesAction(Request $request)
 {

    $em = $this->getDoctrine()->getManager();
    $records = $em->getRepository("AppBundle:Entity")->findAll();

    return $this->render('manage.html.twig', array('entities'=>$records, 'error' => null));
 }


  /**
  * @Route("/entity/delete/{entityid}", name="deleteentity")
  */
public function deleteEntityAction(Request $request, $entityid)
 {


     $em = $this->getDoctrine()->getManager();

     $entity = $em->getRepository('AppBundle:Entity')->find($entityid);

     $em->remove($entity);
     $em->flush();

    $linkedrevisions = $em->getRepository('AppBundle:Revision')->findByIdEntity($entityid);

    foreach ($linkedrevisions as $revision) {
     $em->remove($revision);
    }

    $em->flush();

    $info = "Entity successfully deleted !";

    return $this->render('error.html.twig', array('error'=>$info));
  }




/**
* @Route("/entity/{entityid}/{category}", name="getcategory")
*/
public function getCategorysAction(Request $request, $entityid, $category)
{

  $entity = $this->getDoctrine()
  ->getRepository('AppBundle:Entity')
  ->find($entityid);

  $em = $this->getDoctrine()->getManager();

  $sql = "
SELECT id_revision, date, category_title  FROM revision WHERE id_entity = :idEntity AND category_title = :categoryTitle
UNION
SELECT id_revision, date, category_title  FROM revision WHERE id_entity = :idEntity  AND id_revision NOT IN (SELECT id_revision  FROM revision WHERE id_entity = :idEntity AND category_title = :categoryTitle)
GROUP BY id_revision
ORDER BY  `date` ASC
";

$stmt =$this->getDoctrine()->getManager()->getConnection()->prepare($sql);
$stmt->bindValue("idEntity", $entityid);
$stmt->bindValue("categoryTitle", $category);
$stmt->execute();
$revisions = $stmt->fetchAll();

    // echo '<pre>';
    // \Doctrine\Common\Util\Debug::dump($revisions);
    // echo '</pre>';
    //
    //
    // echo "<br><br><br>";



//var_dump($revisions);

return $this->render('category.html.twig', array("entity" => $entity, "revisions" => $revisions, "category" => $category));

}




/**
* @Route("/entity/{entityid}", name="selectedentity")
*/
public function selectedEntityAction(Request $request, $entityid)
{

  //get the entity
  $entity = $this->getDoctrine()
  ->getRepository('AppBundle:Entity')
  ->find($entityid);

  ///////Get all of its revisions///////

  //Only select date and id fields
  $fields = array('r.date', 'r.idRevision');

  $em = $this->getDoctrine()->getManager();
  $revisions = $em->getRepository("AppBundle:Revision")->createQueryBuilder('r')
  ->select($fields)
  ->where('r.idEntity = :idEntity')
  ->setParameter('idEntity', $entityid)
  ->groupBy('r.idRevision')
  ->getQuery()
  ->getResult();


  return $this->render('entity.html.twig', array("entity" => $entity, "revisions" => $revisions ));
}


/**
* @Route("/bulkimport/", name="bulkimport")
*/
public function bulkimportAction(Request $request)
{

   $data = array();

   ///////Create initial form///////

    //Set the action attribute
    $form = $this->createFormBuilder($data, array(
        'action' => "/add/",
    ))
    ->add('entitiesNames', TextareaType::class, array(
    'attr' => array('rows' => '10', 'class' => "textareaimport"),
    ))
    ->add('save', SubmitType::class)
    ->getForm();

    $form->handleRequest($request);

    //If some data is submit
    if ($form->isValid()) {
        $data = $form->getData();
        //We filter out number and special character
        $parts = explode("\n", $data['entitiesNames']);
        print_r($parts);

        $imported = array();

        //Clean, trim, and execute import each entity
        foreach ($parts as $key => $value) {
            if($value != '')
            {
            $output = preg_replace( '/[^A-Za-z\~\\s\|]/', '', $value);
            $value = preg_replace( '/\\s/', '_', trim($output));
            echo "<br>".$value;
            $callback=$this->parseEntities($value, "20130101000000", "20160101000000", "500", "no");

            //Stock the result (if import has succeeded or failed) in an array
            $imported[$value] = $callback;
            }

        }
    } else {
        //echo 'no data submitted';
        $imported = array();
    }

  return $this->render('import.html.twig', array('form' => $form->createView(), 'imported' => $imported));

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




// Se servir de ces fonctions

public static function getCategories($revision)
{

  $healthy = array("| ", "|", " ");
  $yummy   = array("", "","_");
  $regex = "/\[\[Category:[^?*-@#]*?\]\]/";

 $newcategories = array();

    if (preg_match_all($regex, $revision["*"], $matches_out)) {

      foreach ($matches_out[0]as $key => $value) {
        $str = substr($value, 11, -2);
        $str = str_replace($healthy, $yummy, $str);
        //echo $str."<br>";
        $newcategories[] = $str;
      }

    $revision['categories'] = $newcategories;
    unset($revision['*']);

    }

  return $revision;

}

public static function get1Revisions($data)
{

    reset($data);
    $key = key($data);

    $revision = array();

      foreach ($data[$key]["revisions"] as $key => $val) {
        $revisions[] = self::getCategories($val);
      }
      return $revisions;

}


// public static function checkContinue($data)
// {
//     (isset($data["continue"]["rvcontinue"])) ? $cont = $data["continue"]["rvcontinue"] : $cont = null;

//     return $cont;
// }


// public static function addContinue($continue, $url)
// {
//   if($continue != null && $continue != 'n')
//   {
//     $url = $url."&rvcontinue=".$continue;
//   }

//   return $url;
// }



public function createEntity($entityname)
{

  list($pageid, $extract)=self::getDescription($entityname);

  if($pageid == null)
  {
    echo "<br><b>Entity ".$pageid." not found.<b><br>";
    return 0;
  }

  else
      {
          $type = self::getType($pageid);
          $thumbnail = self::getThumbnail($pageid);

          //We call the Service 'databasemanager'
          $controller = $this->get('databasemanager');
          $controller->createEntity($pageid, $entityname, $type, $extract, $thumbnail);
        }

  return $pageid;
}



public function createRevision($title)
{

  list($pageid, $extract)=self::getDescription($title);

  if($pageid == null)
  {
    echo "<br><b>Entity ".$title." not found.<b><br>";
    return 0;
  }

  else
      {
          $type = self::getType($title);
          $thumbnail = self::getThumbnail($title);

          //We call the Service 'databasemanager'
          $controller = $this->get('databasemanager');
          $controller->createEntity($pageid, $title, $type, $extract, $thumbnail);
        }

  return $pageid;
}







public function parseEntities($entityname, $start, $end, $limit, $continue)
{           

         $entityid= $this->createRevision($entityname);

         if($entityid == 0)
         {
          return 0;
         }

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

            return 1;
}





public static function getDescription($slug){
  $url = "http://dbpedia.org/sparql?default-graph-uri=http%3A%2F%2Fdbpedia.org&query=select+distinct+%3Fid+%3Fabstract+where+%7B%0D%0A%0D%0A%3Chttp%3A%2F%2Fdbpedia.org%2Fresource%2F".$slug."%3E+%3Chttp%3A%2F%2Fdbpedia.org%2Fontology%2FwikiPageID%3E+%3Fid+.%0D%0A%3Chttp%3A%2F%2Fdbpedia.org%2Fresource%2F".$slug."%3E+%3Chttp%3A%2F%2Fdbpedia.org%2Fontology%2Fabstract%3E+%3Fabstract%0D%0A%0D%0AFILTER+langMatches%28lang%28%3Fabstract%29%2C%27en%27%29%0D%0A%7D+LIMIT+2&format=application%2Fsparql-results%2Bjson&timeout=30000";

  //var_dump($url);

  $response = self::curl($url);

  $data = json_decode($response, true);

  if(isset($data['results']['bindings'][0]['id']['value']))
  {
      $pageid= $data['results']['bindings'][0]['id']['value'];

      //echo "<br><br>LE PAGE ID <br>".$pageid;
      //
      $extract= $data['results']['bindings'][0]['abstract']['value'];

      //echo "<br><br>LE EPIC EXTRACT <br>".$extract;
      
      $lereturn = array($pageid, $extract);
  }

  else
    {
        $lereturn = array(null, null);
    }


  return $lereturn;
}


public static function getDescriptionById($id)
{
  $url = "http://dbpedia-live.openlinksw.com/sparql?default-graph-uri=http%3A%2F%2Fdbpedia.org&query=SELECT+%3Furi+%3Fabstract+%3Flabel%0D%0A+WHERE+%7B%0D%0A%3Furi+%3Chttp%3A%2F%2Fdbpedia.org%2Fontology%2FwikiPageID%3E+".$id."+.%0D%0A%0D%0A%3Furi+%3Chttp%3A%2F%2Fdbpedia.org%2Fontology%2Fabstract%3E+%3Fabstract+.%0D%0A%0D%0A%3Furi+%3Chttp%3A%2F%2Fwww.w3.org%2F2000%2F01%2Frdf-schema%23label%3E+%3Flabel%0D%0AFILTER+langMatches%28lang%28%3Fabstract%29%2C%27en%27%29%0D%0A%0D%0A%7D+LIMIT+100%0D%0A&should-sponge=&format=json";


  var_dump($url);

  $response = self::curl($url);

  $data = json_decode($response, true);



  if(isset($data['results']['bindings']))
  {
      var_dump($data['results']['bindings'][0]);
      $title= $data['results']['bindings'][0]['label']['value'];
      echo "<br><br>LE PAGE TITLE <br>".$title;

      $extract= $data['results']['bindings'][0]['abstract']['value'];
      echo "<br><br>LE EPIC EXTRACT <br>".$extract;
      $lereturn = array($title, $extract);
  }

  else
    {
        $lereturn = array(null, null);
    }

  return $lereturn;
}

public static function getType($slug)
{

  $url = "http://dbpedia.org/sparql?default-graph-uri=http%3A%2F%2Fdbpedia.org&query=select+distinct+%3Ftype+where+%7B%0D%0A%0D%0A%3Chttp%3A%2F%2Fdbpedia.org%2Fresource%2F".$slug."%3E%09%3Chttp%3A%2F%2Fwww.w3.org%2F1999%2F02%2F22-rdf-syntax-ns%23type%3E%09%3Ftype+.%0D%0A%0D%0A%7D+LIMIT+100&format=application%2Fsparql-results%2Bjson&CXML_redir_for_subjs=121&CXML_redir_for_hrefs=&timeout=30000&debug=on";

  $response = self::curl($url);

  $data = json_decode($response, true);

  if(isset($data['results']['bindings'][0]['type']['value']))
  {
    $type= $data['results']['bindings'][0]['type']['value'];

    if (($tmp = strstr($type, '#')) !== false) {
      $type = substr($tmp, 1);

      //echo "<br>LE TYPE ".$type."<br>";
      //
    }
  }

  else
  {
    $type = "Unknown";
  }


  return $type;

}

public static function getThumbnail($slug)
{
  $url = "http://dbpedia.org/sparql?default-graph-uri=http%3A%2F%2Fdbpedia.org&query=prefix+dbpedia%3A+%3Chttp%3A%2F%2Fdbpedia.org%2Fresource%2F%3E%0D%0Aprefix+dbpedia-owl%3A+%3Chttp%3A%2F%2Fdbpedia.org%2Fontology%2F%3E%0D%0A%0D%0Aselect+%3Fthumbnail+where+%7B+%0D%0A++dbpedia%3A".$slug."+dbpedia-owl%3Athumbnail+%3Fthumbnail+.%0D%0A%7D&format=json&CXML_redir_for_subjs=121&CXML_redir_for_hrefs=&timeout=30000&debug=on";

  //var_dump($url);

  $response = self::curl($url);

  $data = json_decode($response, true);

  if(isset($data['results']['bindings'][0]['thumbnail']['value']))
  {
    $thumbnail = $data['results']['bindings'][0]['thumbnail']['value'];
    //echo "LA THUMBNAIL : <br>".$thumbnail."<br>";
  }
  else
  {
    $thumbnail = "unknown";
  }

  return $thumbnail;

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
