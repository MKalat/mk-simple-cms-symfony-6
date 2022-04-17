<?php

namespace App\Controller;

use App\Entity\Links;
use App\Form\Type\LinkType;
use App\Entity\Pages;
use App\Form\Type\PageType;
use App\Form\Type\UserType;
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
     * @Route("/admin/linksdelete/{id}", name="admin_links_delete")
     */
    public function linksDeleteAction(Request $request, EntityManagerInterface $em, $id)
    {
        $repository = $em->getRepository('App:Links');
        $linkDetails = $repository->findOneBy(array('id' => $id));

        if ($linkDetails) {
            $em->remove($linkDetails);
            $em->flush();
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
        $repository = $em->getRepository('App:Links');
        $linkDetails = $repository->findOneBy(array('id' => $id));

        if (!$linkDetails) {
            $linkDetails = new Links();
        }

        $form = $this->createForm(LinkType::class, $linkDetails);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $linkDetails = $form->getData();
            $em->persist($linkDetails);
            $em->flush();
            return $this->redirectToRoute("admin-links");
        }
        return $this->renderForm('admin/adminlinksdetails.html.twig', array(
            'form' => $form,
        ));
    }

    /**
     * @Route("/admin/linksnew", name="new-link")
     */
    public function linkNewAction(Request $request, EntityManagerInterface $em)
    {
        $linkDetails = new Links();
        $form = $this->createForm(LinkType::class, $linkDetails);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $linkDetails = $form->getData();
            $em->persist($linkDetails);
            $em->flush();
            return $this->redirectToRoute("admin-links");
        }
        return $this->renderForm('admin/adminlinksdetails.html.twig', array(
            'form' => $form,
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
     * @Route("/admin/pagesdelete/{id}", name="admin_pages_delete")
     */
    public function pagesDeleteAction(Request $request, EntityManagerInterface $em, $id)
    {
        $repository = $em->getRepository('App:Pages');
        $pageDetails = $repository->findOneBy(array('id' => $id));

        if ($pageDetails) {
            $em->remove($pageDetails);
            $em->flush();
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
        $repository = $em->getRepository('App:Pages');
        $pageDetails = $repository->findOneBy(array('id' => $id));

        if (!$pageDetails) {
            $pageDetails = new Links();
        }

        $form = $this->createForm(PageType::class, $pageDetails);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pageDetails = $form->getData();
            $em->persist($pageDetails);
            $em->flush();
            return $this->redirectToRoute("admin-pages");
        }
        return $this->renderForm('admin/adminpagesdetails.html.twig', array(
            'form' => $form,
        ));
    }

    /**
     * @Route("/admin/pagesnew", name="new-page")
     */
    public function pageNewAction(Request $request, EntityManagerInterface $em)
    {
        $pageDetails = new Pages();
        $form = $this->createForm(PageType::class, $pageDetails);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pageDetails = $form->getData();
            $em->persist($pageDetails);
            $em->flush();
            return $this->redirectToRoute("admin-pages");
        }
        return $this->renderForm('admin/adminpagesdetails.html.twig', array(
            'form' => $form,
        ));
    }

    /**
     * @Route("/admin/users", name="admin-users")
     */
    public function usersAction()
    {
        return $this->render('admin/users.html.twig');
    }

    /**
     * @Route("/admin/usersAjax", name="admin_usersAjax")
     */
    public function usersAjaxAction(Request $request, Connection $connection, DataTablesSupport $dtSupp)
    {
        $cols = array(
            array( "db" => "ID", "dt" => "0" ),
            array( "db" => "username", "dt" => "1" ),
            array( "db" => "password", "dt" => "2" ),
            array( "db" => "email", "dt" => "3" ),
            array( "db" => "is_active", "dt" => "4" ),
        );

        $limitSql = $dtSupp->limitData($request->query->all());
        $orderSql = $dtSupp->orderData($request->query->all(), $cols);
        $filterSql = $dtSupp->filterData($request->query->all(), $cols);

        $logs_total = $connection->fetchAllAssociative("SELECT id, username, password, email, is_active FROM `users` ");

        $logs_raw = $connection->fetchAllAssociative("SELECT id, username, password, email, is_active FROM `users` ".$filterSql.' '.$orderSql.' '.$limitSql);

        $response = array();
        $response['draw'] = intval($request->query->get('draw'));
        $response["recordsTotal"] = count($logs_total);
        $response["recordsFiltered"] = count($logs_total);
        $response['data'] = array();

        foreach ($logs_raw as $item) {
            $response['data'][] = [$item['id'], $item['username'], $item['password'], $item['email'], $item['is_active']];
        }

        return new Response(json_encode($response));
    }

    /**
     * @Route("/admin/usersdetails/{id}", name="admin_users_details")
     */
    public function usersdetailsAction(Request $request, EntityManagerInterface $em, $id)
    {
        $repository = $em->getRepository('App:User');
        $userDetails = $repository->findOneBy(array('id' => $id));

        if (!$userDetails) {
            $userDetails = new User();
        }

        $form = $this->createForm(UserType::class, $userDetails);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userDetails = $form->getData();
            $em->persist($userDetails);
            $em->flush();
            return $this->redirectToRoute("admin-users");
        }
        return $this->renderForm('admin/adminusersdetails.html.twig', array(
            'form' => $form,
        ));
    }

    /**
     * @Route("/admin/usersnew", name="new-user")
     */
    public function userNewAction(Request $request, EntityManagerInterface $em)
    {
        $userDetails = new User();
        $form = $this->createForm(UserType::class, $userDetails);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userDetails = $form->getData();
            $em->persist($userDetails);
            $em->flush();
            return $this->redirectToRoute("admin-users");
        }
        return $this->renderForm('admin/adminusersdetails.html.twig', array(
            'form' => $form,
        ));
    }

    /**
     * @Route("/admin/usersdelete/{id}", name="admin_users_delete")
     */
    public function usersDeleteAction(Request $request, EntityManagerInterface $em, $id)
    {
        $repository = $em->getRepository('App:User');
        $userDetails = $repository->findOneBy(array('id' => $id));

        if ($userDetails) {
            $em->remove($userDetails);
            $em->flush();
        }

        return $this->redirectToRoute("admin-users");
    }
}
