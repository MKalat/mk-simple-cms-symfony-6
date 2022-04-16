<?php

namespace App\Controller;

use App\Entity\Links;
use App\Entity\Pages;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\DataTablesSupport;
use Doctrine\DBAL\Connection;
use App\Entity\User;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin-index")
     */
    public function indexAction(EntityManagerInterface $em)
    {
        $em = $em->getRepository('App:LoginLogs');

        $logs_records = $em->findBy(array(), array('czas'=>'DESC'), 0, 1);

        if ($logs_records) {
            $last_log_record = $logs_records[0];

            $name = $last_log_record['login'];
        } else {
            $name = '';
        }

        return $this->render('admin/index.html.twig', array('lastadmin' => $name));
    }

    /**
     * @Route("/admin/loginlogs", name="admin-loginlogs")
     */
    public function loginlogsAction(Request $request)
    {
        return $this->render('admin/loginlogs.html.twig');
    }

    /**
     * @Route("/admin/loginlogsAjax", name="admin_adminlogsAjax")
     */
    public function loginlogsAjaxAction(Request $request, Connection $connection, DataTablesSupport $dtSupp)
    {

        $cols = array(
            array( "db" => "ID", "dt" => "0" ),
            array( "db" => "ip", "dt" => "1" ),
            array( "db" => "login", "dt" => "2" ),
            array( "db" => "czas", "dt" => "3" ),
            array( "db" => "status", "dt" => "4" ),
            array( "db" => "ranga", "dt" => "5" ),
        );

        $limitSql = $dtSupp->limitData($request->query->all());
        $orderSql = $dtSupp->orderData($request->query->all(), $cols);
        $filterSql = $dtSupp->filterData($request->query->all(), $cols);

        $logs_total = $connection->fetchAllAssociative("SELECT id, ip, login, czas, status, ranga FROM loginlogs ");

        $logs_raw = $connection->fetchAllAssociative("SELECT id, ip, login, czas, status, ranga FROM loginlogs ".$filterSql.' '.$orderSql.' '.$limitSql);

        $response = array();
        $response['draw'] = intval($request->query->get('draw'));
        $response["recordsTotal"] = count($logs_total);
        $response["recordsFiltered"] = count($logs_total);
        $response['data'] = array();

        foreach ($logs_raw as $item) {
            $response['data'][] = [$item['id'], $item['ip'], $item['login'], $item['czas'], $item['status'], $item['ranga']];
        }

        return new Response(json_encode($response));
    }

    /**
     * @Route("/admin/links", name="admin-links")
     */
    public function linksAction()
    {
        return $this->render('admin/links.html.twig');
    }

    /**
     * @Route("/admin/linksdelete", name="admin_links_delete")
     */
    public function linksDeleteAction(Request $request, EntityManagerInterface $em)
    {
        $data = $request->get('dataTables');
        $ids  = $data['actions'];

        $qb = $em->createQueryBuilder();
        $qb->select('l');
        $qb->from('App:Links', 'l');
        $qb->where($qb->expr()->in('l.id', $ids));

        //ArrayCollection
        $result = $qb->getQuery()->getResult();

        if ($result) {
            foreach ($result as $link) {
                $em->remove($link);
                $em->flush();
            }
        }
        return $this->redirectToRoute("admin-links");
    }

    /**
     * @Route("/admin/linksAjax", name="admin_linksAjax")
     */
    public function linksAjaxAction(Request $request, Connection $connection, DataTablesSupport $dtSupp)
    {
        $cols = array(
            array( "db" => "ID", "dt" => "0" ),
            array( "db" => "pozycja", "dt" => "1" ),
            array( "db" => "etykieta", "dt" => "2" ),
            array( "db" => "link", "dt" => "3" ),
            array( "db" => "strona", "dt" => "4" ),
            array( "db" => "lang", "dt" => "5" ),
        );

        $limitSql = $dtSupp->limitData($request->query->all());
        $orderSql = $dtSupp->orderData($request->query->all(), $cols);
        $filterSql = $dtSupp->filterData($request->query->all(), $cols);

        $logs_total = $connection->fetchAllAssociative("SELECT id, pozycja, etykieta, link, strona, lang FROM links ");

        $logs_raw = $connection->fetchAllAssociative("SELECT id, pozycja, etykieta, link, strona, lang FROM links ".$filterSql.' '.$orderSql.' '.$limitSql);

        $response = array();
        $response['draw'] = intval($request->query->get('draw'));
        $response["recordsTotal"] = count($logs_total);
        $response["recordsFiltered"] = count($logs_total);
        $response['data'] = array();

        foreach ($logs_raw as $item) {
            $response['data'][] = [$item['id'], $item['pozycja'], $item['etykieta'], $item['link'], $item['strona'], $item['lang']];
        }

        return new Response(json_encode($response));
    }

    /**
     * @Route("/admin/linksdetails/{id}", name="admin_links_details")
     */
    public function linksdetailsAction(Request $request, EntityManagerInterface $em, $id)
    {
        //$id = $request->query->get('id');
        $action = $request->request->get('Action');
        $repository = $em->getRepository('App:Links');

        $linkDetails = $repository->findOneBy(array('id' => $id));

        if ($request->getRealMethod() == 'POST') {
            if (!$linkDetails) {
                $linkDetails = new Links();
            }
            if ($action == 'Save') {
                $linkDetails->setPozycja($request->request->get('pozycja'));
                $linkDetails->setEtykieta($request->request->get('etykieta'));
                $linkDetails->setLink($request->request->get('link'));
                $linkDetails->setStrona($request->request->get('strona'));
                $linkDetails->setLang($request->request->get('lang'));

                $entityManager = $em;
                $entityManager->persist($linkDetails);
                $entityManager->flush();
            } elseif ($action == 'Delete') {
                if ($linkDetails) {
                    $em->remove($linkDetails);
                    $em->flush();
                }
            }
            return $this->redirectToRoute("admin-links");
        }
        if (!$linkDetails) {
            $linkDetails = '';
        }

        return $this->render('admin/adminlinksdetails.html.twig', array(
            'logs' => $linkDetails,

        ));
    }

    /**
     * @Route("/admin/linksnew", name="new-link")
     */
    public function linkNewAction(Request $request, EntityManagerInterface $em)
    {
        $linkDetails = new Links();
        if ($request->getRealMethod() == 'POST') {
            $linkDetails->setPozycja($request->request->get('pozycja'));
            $linkDetails->setEtykieta($request->request->get('etykieta'));
            $linkDetails->setLink($request->request->get('link'));
            $linkDetails->setStrona($request->request->get('strona'));
            $linkDetails->setLang($request->request->get('lang'));

            $entityManager = $em;
            $entityManager->persist($linkDetails);
            $entityManager->flush();
        }
        return $this->render('admin/adminlinksdetails.html.twig', array(
            'logs' => $linkDetails

        ));
    }

    /**
     * @Route("/admin/pages", name="admin-pages")
     */
    public function pagesAction()
    {
        return $this->render('admin/pages.html.twig');
    }

    /**
     * @Route("/admin/pagesdelete", name="admin_pages_delete")
     */
    public function pagesDeleteAction(Request $request, EntityManagerInterface $em)
    {
        $data = $request->get('dataTables');
        $ids  = $data['actions'];

        $qb = $em->createQueryBuilder();
        $qb->select('p');
        $qb->from('App:Pages', 'p');
        $qb->where($qb->expr()->in('p.id', $ids));

        //ArrayCollection
        $result = $qb->getQuery()->getResult();

        if ($result) {
            foreach ($result as $page) {
                $em->remove($page);
                $em->flush();
            }
        }
        return $this->redirectToRoute("admin-pages");
    }

    /**
     * @Route("/admin/pagesAjax", name="admin_pagesAjax")
     */
    public function pagesAjaxAction(Request $request, Connection $connection, DataTablesSupport $dtSupp)
    {
        $cols = array(
            array( "db" => "ID", "dt" => "0" ),
            array( "db" => "etykieta", "dt" => "1" ),
            array( "db" => "link", "dt" => "2" ),
            array( "db" => "lang", "dt" => "3" ),
        );

        $limitSql = $dtSupp->limitData($request->query->all());
        $orderSql = $dtSupp->orderData($request->query->all(), $cols);
        $filterSql = $dtSupp->filterData($request->query->all(), $cols);

        $logs_total = $connection->fetchAllAssociative("SELECT id, etykieta, link, lang FROM pages ");

        $logs_raw = $connection->fetchAllAssociative("SELECT id, etykieta, link, lang FROM pages ".$filterSql.' '.$orderSql.' '.$limitSql);

        $response = array();
        $response['draw'] = intval($request->query->get('draw'));
        $response["recordsTotal"] = count($logs_total);
        $response["recordsFiltered"] = count($logs_total);
        $response['data'] = array();

        foreach ($logs_raw as $item) {
            $response['data'][] = [$item['id'], $item['etykieta'], $item['link'], $item['lang']];
        }

        return new Response(json_encode($response));
    }

    /**
     * @Route("/admin/pagesdetails/{id}", name="admin_pages_details")
     */
    public function pagesdetailsAction(Request $request, EntityManagerInterface $em, $id)
    {
        //$id = $request->query->get('id');
        $action = $request->request->get('Action');
        $repository = $em->getRepository('App:Pages');

        $pageDetails = $repository->findOneBy(array('id' => $id));

        if ($request->getRealMethod() == 'POST') {
            if (!$pageDetails) {
                $pageDetails = new Pages();
            }
            if ($action == 'Save') {
                $pageDetails->setEtykieta($request->request->get('etykieta'));
                $pageDetails->setLink($request->request->get('link'));
                $pageDetails->setLang($request->request->get('lang'));
                $pageDetails->setContent($request->request->get('content'));

                $entityManager = $em;
                $entityManager->persist($pageDetails);
                $entityManager->flush();
            } elseif ($action == 'Delete') {
                if ($pageDetails) {
                    $em->remove($pageDetails);
                    $em->flush();
                }
            }
            return $this->redirectToRoute("admin-pages");
        }

        if (!$pageDetails) {
            $pageDetails = '';
        }

        return $this->render('admin/adminpagesdetails.html.twig', array(
            'logs' => $pageDetails,

        ));
    }

    /**
     * @Route("/admin/pagesnew", name="new-page")
     */
    public function pageNewAction(Request $request, EntityManagerInterface $em)
    {
        $pageDetails = new Pages();
        if ($request->getRealMethod() == 'POST') {
            $pageDetails->setEtykieta($request->request->get('etykieta'));
            $pageDetails->setLink($request->request->get('link'));
            $pageDetails->setLang($request->request->get('lang'));
            $pageDetails->setContent($request->request->get('content'));

            $entityManager = $em;
            $entityManager->persist($pageDetails);
            $entityManager->flush();
        }

        return $this->render('admin/adminpagesdetails.html.twig', array(
            'logs' => $pageDetails

        ));
    }
}
