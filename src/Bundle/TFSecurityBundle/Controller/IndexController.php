<?php

namespace Trollfjord\Bundle\TFSecurityBundle\Controller;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Knp\Menu\MenuItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use Trollfjord\Bundle\PublicUserBundle\Entity\User;
use Trollfjord\Bundle\QuestionnaireBundle\Entity\Category;
use Trollfjord\Bundle\TFSecurityBundle\Entity\SpamMail;
use Trollfjord\Bundle\TFSecurityBundle\Repository\SpamMailRepository;
use Trollfjord\Core\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use  DateTime;
use SplFileObject;
/**
 * Class IndexController
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 *
 * @Route("/TFSecurity", name="TFSecurity_")
 */
class IndexController extends AbstractController
{


    public function __construct(MailerInterface $mailer)
    {
        parent::__construct($mailer);
        //$this->myService = $myService;
    }

    /**
     * @Route("/", name="home")
     * @return Response|JsonResponse
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function index( Request $request, MailerInterface $mailer)
    {
        $theMails = [];



        return $this->render('@TFSecurity/index.html.twig', [
            'mails' => ""
        ]);
    }

    /**
     * @Route("/getSpammails", name="get_spam")
     * @param Request  $request
     * @return Response|JsonResponse
     * @throws NonUniqueResultException|NoResultException
     */
    public function getSpammails( Request $request, MailerInterface $mailer)
    {
        $theMails = [];

        if ($request->isXmlHttpRequest()) {





            /** @var SpamMailRepository $sr */
            $sr = $this->getDoctrine()->getRepository(SpamMail::class);

            if ($request->isMethod(Request::METHOD_POST)) {

                $em = $this->getDoctrine()->getManager();
                switch ($request->get('action', null)) {
                    case "delete_spammail":
                        $r = $sr->find($request->get('spammail_id', null));
                        $em->remove($r);
                        $em->flush();
                        break;
                    case "resend_spammail":
                        $spamMail = $sr->find($request->get('spammail_id', null));
                        $email = (new Email())
                            ->subject($spamMail->getSubject())
                            ->from(new \Symfony\Component\Mime\Address('support@schultransform.org', 'Schultransform'))
                            ->to($request->get('send_to', null))
                            ->html($spamMail->getBody());

                        $mailer->send($email);
                        $spamMail->setStatus(2);
                        $em->flush();

                        break;
                }
            }


            return new JsonResponse($sr->find4Ajax(
                $request->query->getAlnum('sort', 'creationDate'),
                $request->query->getBoolean('sortDesc', false),
                $request->query->getInt('page', 1),
                $request->query->getInt('size', 20)
            ));
        }



        $sr = $this->getDoctrine()->getRepository(SpamMail::class);

        return new JsonResponse($sr->find4Ajax(
            $request->query->getAlnum('sort', 'creationDate'),
            $request->query->getBoolean('sortDesc', false),
            $request->query->getInt('page', 1),
            $request->query->getInt('size', 20)
        ));
    }


    /**
     * @Route("/delete/{id<\d+>?}",
     *     name="delete",
     *     defaults={"id": null}
     * )
     * @param Request                $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(
        Request                $request,
        EntityManagerInterface $entityManager
    ): Response {
        $id = $request->attributes->get("id");
        /** @var SpamMail $spam
         */
        $spam = $entityManager->getRepository(SpamMail::class)->findOneBy(['id' => $id]);
        if ($spam instanceof SpamMail) {
            $spam->remove();
            $entityManager->persist($spam);
            $entityManager->flush($spam);
            $this->addFlash('success', 'Spammail wurde erfolgreich gelöscht');

            return new JsonResponse(true);
        } else {
            return new JsonResponse(['message' => 'Spammail nicht gefunden!']);
        }
    }


    /**
     * @Route("/erroroverview", name="erroroverview")
     * @return Response
     */

    public function errorLogs()
    {

        $logDirectory = $this->getParameter('kernel.logs_dir');

        // Get all the log files in the log directory
        $files = scandir($logDirectory);

        $logFiles = [];
        foreach ($files as $file) {
            $bigFile = false;
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $logDirectory . '/' . $file;
            $fileInfo = new File($filePath);

            // Get file information
            $lastUpdate = $fileInfo->getMTime(); // Last update time (Unix timestamp)
            $size = $fileInfo->getSize(); // Size in bytes
            $size = $size / 1024 / 1024;
            if($size>100){
                $bigFile = true;
            }
            $size = number_format($size, 2,',','.');

            // Convert Unix timestamps to readable format
            $lastUpdate = date('d.m.Y H:i:s', $lastUpdate);

            $logFiles[] = [
                'filename' => $file,
                'last_update' => $lastUpdate,
                'size' => $size,
                'bigfile' => $bigFile
            ];
        }

        return $this->render('@TFSecurity/logs.html.twig', [
            'logFiles' => $logFiles,
        ]);
    }


      /**
     * @Route("/logfile/{filename}", name="view_logfile")
     * @param string $filename
     * @return Response
     */

    public function viewLogfile(Request $request, string $filename)
    {
        $logDirectory = $this->getParameter('kernel.logs_dir');
        $filePath = $logDirectory . '/' . $filename;

        // Read the contents of the log file
        $contents = file_get_contents($filePath);

        // Split the contents into lines
        $lines = explode("\n", $contents);

        $logsPerPage = 20; // Number of logs to display per page
        $totalLogs = count($lines);

        $totalPages = ceil($totalLogs / $logsPerPage);


        // Get the current page number from the request query parameters
        $page = $request->query->getInt('page', 1);

        // Calculate the start and end indexes for the logs on the current page
        $startIndex = ($page - 1) * $logsPerPage;
        $endIndex = $startIndex + $logsPerPage;

        $logs = [];

        // Loop through the lines and extract the details for the current page
        for ($i = $startIndex; $i < $endIndex; $i++) {
            if (isset($lines[$i])) {
                $line = $lines[$i];

                // Extract the file, line number, error type, and message from the line
                $matches = [];
                $pattern = '/^\[(.*?)\]\s*(.*?)\.(.*?)\:\s*(.*?)\s*(\{.*?\})?$/';

// Loop through the lines and extract the details for the current page
                for ($i = $startIndex; $i < $endIndex; $i++) {
                    if (isset($lines[$i])) {
                        $line = $lines[$i];

                        // Extract the timestamp, channel name, log level, message, and optional fields from the line
                        $matches = [];
                        if (preg_match($pattern, $line, $matches)) {
                            $timestamp = $matches[1];
                            $dateTime = new DateTime($timestamp);
                            $humanReadableFormat = $dateTime->format('d.m.Y H:i:s');

                            $channel = $matches[2];
                            $logLevel = $matches[3];
                            $message = $matches[4];
                            $optionalFields = isset($matches[5]) ? $matches[5] : '';

                            $logs[] = [
                                'timestamp' => $humanReadableFormat,
                                'channel' => $channel,
                                'logLevel' => $logLevel,
                                'message' => $message,
                                'optionalFields' => $optionalFields,
                            ];
                        }
                    }
                }

            }
        }

        return $this->render('@TFSecurity/view_logfile.html.twig', [
            'filename' => $filename,
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }



    /**
     * @Route("/biglogfile/{filename}", name="view_big_logfile")
     * @param string $filename
     * @return Response
     */

    public function viewBigLogfile(Request $request, string $filename)
    {
        $logDirectory = $this->getParameter('kernel.logs_dir');
        $filePath = $logDirectory . '/' . $filename;
        $logsPerPage = 40; // Number of logs to display per page
        $logs = []; // Initialize an empty array to store the logs
        $currentLines = 0;
        $shownLines = 0;
        $currentLine = $request->query->getInt('line', 0);
        if($currentLine<0){
            $currentLine = $logsPerPage+$currentLine;
        }


        // Open the log file
        $file = new SplFileObject($filePath, 'r');

        if ($file) {
            // Move the file pointer to the end of the file
           if($currentLine==0){
            $file->seek(PHP_INT_MAX);
            // and use that as line number
            $currentLine = $file->key() + 1;
           }

            // Move the file pointer to the beginning of the last line
            $ll = max(0, $currentLine - $logsPerPage);
            $file->seek($ll);

            // Read the logsPerPage number of lines
            while (!$file->eof() && count($logs) < $logsPerPage) {
                $line = $file->fgets();

                // Extract the log information from the line
                $matches = [];
                $pattern = '/^\[(.*?)\]\s*(.*?)\.(.*?)\:\s*(.*?)\s*(\{.*?\})?$/';

                if (preg_match($pattern, $line, $matches)) {
                    $timestamp = $matches[1];
                    $dateTime = new DateTime($timestamp);
                    $humanReadableFormat = $dateTime->format('d.m.Y H:i:s');
                    $channel = $matches[2];
                    $logLevel = $matches[3];
                    $message = $matches[4];
                    $optionalFields = isset($matches[5]) ? $matches[5] : '';

                    $logs[] = [
                        'timestamp' => $humanReadableFormat,
                        'channel' => $channel,
                        'logLevel' => $logLevel,
                        'message' => $message,
                        'optionalFields' => $optionalFields,
                    ];
                    $shownLines++;
                }

                // Increment the line counter

                // Move the file pointer to the next line
                $file->next();
            }

            // Calculate the line numbers for previous and next buttons
            $prevLine = max(1, $currentLine - $logsPerPage);
            $nextLine = $currentLine - $logsPerPage;
            $logs = array_reverse($logs);
            $shownLines = $shownLines - $logsPerPage;
            return $this->render('@TFSecurity/view_big_logfile.html.twig', [
                'filename' => $filename,
                'logs' => $logs,
                'currentLine' => $currentLine,
                'shownLines' => $shownLines,

                'currentLines' => $currentLines,
                'prevLine' => $prevLine,
                'nextLine' => $nextLine,
            ]);
        }
    }

    /**
     * @Route("/searchlogfile", name="search_logfile")
     * @param string $filename
     * @return Response
     */

    public function searchLog(Request $request): Response
    {

        $logDirectory = $this->getParameter('kernel.logs_dir');
        $logFiles = [];

        $searchTerm = $request->query->get('search');
        $selectedFile = $request->query->get('file');
        $logFile = $logDirectory . '/' . $selectedFile;
        $logs = [];

        $files = scandir($logDirectory);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $logFiles[] = $file;
            }
        }




        $escapedSearchTerm = escapeshellarg($searchTerm);
        $command = "grep '$escapedSearchTerm' '$logFile'";
        $output = shell_exec($command);


        $lines = explode(PHP_EOL, trim($output)); // Split output into lines
        $matches = [];
        // Process each line of the output
        $processedLines = [];
        foreach ($lines as $line) {
            // Perform actions on each line
            // Example: Convert the line to uppercase
            $processedLines[] = strtoupper($line);


            $pattern = '/^\[(.*?)\]\s*(.*?)\.(.*?)\:\s*(.*?)\s*(\{.*?\})?$/';

            $matches = [];
            if (preg_match($pattern, $line, $matches)) {
                $timestamp = $matches[1];
                $dateTime = new DateTime($timestamp);
                $humanReadableFormat = $dateTime->format('d.m.Y H:i:s');

                $channel = $matches[2];
                $logLevel = $matches[3];
                $message = $matches[4];
                $optionalFields = isset($matches[5]) ? $matches[5] : '';

                $logs[] = [
                    'timestamp' => $humanReadableFormat,
                    'channel' => $channel,
                    'logLevel' => $logLevel,
                    'message' => $message,
                    'optionalFields' => $optionalFields,
                ];
            }

        }


        return $this->render('@TFSecurity/search.html.twig', [
            'searchTerm' => $searchTerm,
            'selectedFile' => $selectedFile,
            'logFiles' => $logFiles,
            'logOutput' => $logs,
        ]);

    }

}
