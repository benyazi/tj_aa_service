<?php
namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class SessionController extends AbstractController
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/view/{contentId}")
     */
    public function viewAction(Request $request, $contentId)
    {
        $contentId = (int) $contentId;
        if(!is_int($contentId)) {
            throw $this->createAccessDeniedException('OLOLOLO');
        }
        $client = new \Benyazi\CmttPhp\Api(\Benyazi\CmttPhp\Api::TJOURNAL);
        $content = $client->getEntryById($contentId);
//        var_dump($content);
        $authorId = $content['author']['id'];
        $contentTitle = $content['title'];

        $sql = 'SELECT id, tj_id, creator_tj_id, text, url, content_tj_id, reply_to_tj_id, comment_data '.
            'FROM comment '.
            'WHERE content_tj_id = ?;';
//        var_dump($sql);
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $contentId);
        $stmt->execute();
//        var_dump($stmt->fetchAll());
        $questions = [];
        foreach ($stmt->fetchAll() as $commentItem) {
            if($commentItem['reply_to_tj_id']) {
                if($commentItem['creator_tj_id'] == $authorId) {
                    if(isset($questions[$commentItem['reply_to_tj_id']])) {
                        $commentData = json_decode($commentItem['comment_data'], true);
                        $questions[$commentItem['reply_to_tj_id']]['answer'] = [
                            'name' => $commentData['creator']['name'],
                            'avatar' => $commentData['creator']['avatar'],
                            'text' => $commentItem['text']
                        ];
                        continue;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            $commentData = json_decode($commentItem['comment_data'], true);
            $questions[$commentItem['tj_id']] = [
                'name' => $commentData['creator']['name'],
                'avatar' => $commentData['creator']['avatar'],
                'question' => $commentItem['text'],
                'url' => $commentItem['url'],
                'answer' => null,
            ];
        }
        return $this->render('view.html.twig', [
            'questions' => $questions,
            'contentTitle' => $contentTitle,
        ]);
    }

}