<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Security\Voter\ProjectVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/projects', name: 'admin.project.')]
class ProjectController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    #[IsGranted(ProjectVoter::LIST)]
    public function index(ProjectRepository $projectRepository): Response
    {
        /** @var UserInterface $user */
        $user = $this->security->getUser();
        $userId = $user->getId();

        $projects = $projectRepository->findAllWithTasksByUser($userId);

        return $this->render('admin/project/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    #[IsGranted(ProjectVoter::ADD)]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->security->getUser();

        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $project->setUser($user);

            $em->persist($project);
            $em->flush();

            $this->addFlash(
                'success',
                'New project added successfully'
            );

            return $this->redirectToRoute('admin.project.index');
        }

        return $this->render('admin/project/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted(ProjectVoter::EDIT, subject: 'project')]
    public function edit(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('removeMedia')->getData()) {
                $media = $project->getMedia();
                $project->setMedia(null);
                $em->remove($media);
            } else {
                $mediaFile = $form->get('mediaFile')->getData();
                if ($mediaFile) {
                    $media = new Media();
                    $media->setThumbnailFile($mediaFile);
                    $media->setProject($project);
                    $project->setMedia($media);
                    $em->persist($media);
                }
            }

            $em->flush();

            $this->addFlash(
                'success',
                'Project edited successfully'
            );

            return $this->redirectToRoute('admin.project.index');
        }

        return $this->render('admin/project/edit.html.twig', [
            'form' => $form->createView(),
            'formType' => 'edit'
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted(ProjectVoter::EDIT, subject: 'project')]
    public function delete(EntityManagerInterface $em, Project $project): Response
    {

        $media = $project->getMedia();

        if ($media) {
            $em->remove($media);
        }


        $em->remove($project);
        $em->flush();

        $this->addFlash(
            'success',
            'Project deleted successfully'
        );

        return $this->redirectToRoute('admin.project.index');
    }
}
