<?php

namespace App\Action;

use App\Util\ContainerClient;
use App\Util\Utils;
use App\Util\Exception\AppException;
use App\Util\Exception\UnauthorizedException;
use Slim\Http\Stream;

class ProjectWebAction extends ContainerClient
{
    public function runUploadPicture($request, $response, $params)
    {
        $subject = $request->getAttribute('subject');
        $project = $this->helper->getEntityFromId(
            'App:Project', 'pro', $params
        );
        if (!$this->authorization->checkPermission($subject, 'updateProject', $project)) {
            throw new UnauthorizedException();
        }
        if (empty($request->getUploadedFiles()['archivo'])) {
            throw new AppException('No se envió un archivo');
        }
        $file = $request->getUploadedFiles()['archivo'];
        $this->resources['project']->updatePicture($subject, $project, $file);
        return $response->withRedirect($request->getHeaderLine('HTTP_REFERER'));
    }

    public function runUploadDocument($request, $response, $params)
    {
        $subject = $request->getAttribute('subject');
        $project = $this->helper->getEntityFromId(
            'App:Project', 'pro', $params
        );
        if (!$this->authorization->checkPermission($subject, 'updateProject', $project)) {
            throw new UnauthorizedException();
        }
        if (empty($request->getUploadedFiles()['archivo'])) {
            throw new AppException('No se envió un archivo');
        }
        $file = $request->getUploadedFiles()['archivo'];
        $data = $request->getParsedBody();
        $this->resources['project']->createDocument($subject, $project, $data, $file);
        return $response->withRedirect($request->getHeaderLine('HTTP_REFERER'));
    }

    public function showOne($request, $response, $params)
    {
        $subject = $request->getAttribute('subject');
        $project = $this->helper->getEntityFromId(
            'App:Project', 'pro', $params, ['author','category','district']
        );
        $project->makeVisible(['author','field_values']);
        // $projectArr = $project->toArray();
		$projectFields = $this->db->query('App:ProjectFields')->where('chapter', $project->chapter)->first();
        // if (!$this->authorization->checkPermission($subject, 'showPrivateFieldsProject', $project)) {
        //     $this->logger->info('Not the author nor the admin. Hiding values');
        //     $this->resources['project']->hidePrivateFieldValues($projectArr, $projectFields['private']);
        // }
        return $this->view->render($response, 'project/overview.twig', [
            'project' => $project,
            'projectFields' => $projectFields->toArray()
        ]);
    }

    public function showOneBudget($request, $response, $params)
    {
        $project = $this->helper->getEntityFromId(
            'App:Project', 'pro', $params, ['author','category','district']
        );
        $project->makeVisible(['budget','author']);
		$projectFields = $this->db->query('App:ProjectFields')->where('chapter', $project->chapter)->first();
        return $this->view->render($response, 'project/overview-budget.twig', [
            'project' => $project,
            'projectFields' => $projectFields->toArray()
        ]);
    }
    public function showOneJournal($request, $response, $params)
    {
        $project = $this->helper->getEntityFromId(
            'App:Project', 'pro', $params, ['author','category','district']
        );
        $project->makeVisible(['journal', 'author']);
		$projectFields = $this->db->query('App:ProjectFields')->where('chapter', $project->chapter)->first();
        $userArr = array();
        foreach ($project->journal as $key => $value) {
            $user = $this->helper->getEntityFromId(
            'App:User', $value['author_id'], null, ['subject']
            );
            $userArr[$value['author_id']] = $user->subject->toDummy()->toArray();
        }
        $projectArr = $project->toArray();
        $auxJournal = array();
        foreach ($projectArr['journal'] as $key => $value) {
            $this->resources['project']->hidePrivateFieldValues($value, $projectFields['private']);
            $auxJournal[$key] = $value;
        }
        $projectArr['journal'] = $auxJournal;
        return $this->view->render($response, 'project/overview-journal.twig', [
            'project' => $projectArr,
            'projectFields' => $projectFields->toArray(),
            'users' => $userArr,
        ]);
    }

    public function showPicture($request, $response, $params)
    {
        $path = 'project/' . $this->helper->getSanitizedId('pro', $params) . '.jpg';
        if (!$this->filesystem->has($path)) {
            throw new \App\Util\Exception\AppException(
                'El documento no se encuentra almacenado', 404
            );
        }
        return $response
            ->withBody(new \Slim\Http\Stream($this->filesystem->readStream($path)))
            ->withHeader('Content-Type', $this->filesystem->getMimetype($path));
    }

    public function showDocument($request, $response, $params)
    {
        $subject = $request->getAttribute('subject');
        $project = $this->helper->getEntityFromId(
            'App:Project', 'pro', $params
        );
        if (!$this->authorization->checkPermission($subject, 'updateProject', $project)) {
            throw new UnauthorizedException();
        }
        $document = $this->helper->getEntityFromId(
            'App:Document', 'doc', $params
        );
        $fileData = $this->resources['project']->getDocumentFile($document);
        return $response->withBody(new Stream($fileData['strm']))
        ->withHeader('Content-Type', $fileData['mime']);
    }

    public function deleteDocument($request, $response, $params)
    {
        $subject = $request->getAttribute('subject');
        $project = $this->helper->getEntityFromId(
            'App:Project', 'pro', $params
        );
        if (!$this->authorization->checkPermission($subject, 'updateProject', $project)) {
            throw new UnauthorizedException();
        }
        $document = $this->helper->getEntityFromId(
            'App:Document', 'doc', $params
        );
        $this->resources['project']->deleteDocument($subject ,$document);
        return $response->withRedirect($request->getHeaderLine('HTTP_REFERER'));

    }
}