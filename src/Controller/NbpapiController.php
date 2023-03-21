<?php

namespace App\Controller;

use App\Service\Nbpapi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NbpapiController extends AbstractController
{
    #[Route('/nbpapi', name: 'app_nbpapi')]
    public function index(Request $request, Nbpapi $Nbpapi): Response
    {
        $responseData = null;
        $responseDataHeaders = null;
        $form = $this->createFormBuilder()
            ->add(
                'startDate',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'attr' => ['max' => date('Y-m-d')],
                    'constraints' => [
                        new Constraints\NotBlank(),
                        new Constraints\Type("datetime"),
                    ],
                ]
            )
            ->add(
                'stopDate',
                DateType::class,
                [
                    'widget' => 'single_text',
                    'attr' => ['max' => date('Y-m-d')],
                    'constraints' => [
                        new Constraints\NotBlank(),
                        new Constraints\Type("datetime"),
                        new Constraints\Callback(function ($object, ExecutionContextInterface $context) {
                            $start = $context->getRoot()->getData()['startDate'];
                            $stop = $object;
                            if (is_a($start, \DateTime::class) && is_a($stop, \DateTime::class)) {
                                $datediff = $stop->format('U') - $start->format('U');
                                if ($datediff < 0) {
                                    $context
                                        ->buildViolation('Stop must be after start')
                                        ->addViolation();
                                } elseif (round($datediff / (60 * 60 * 24)) > 7) {
                                    $context
                                        ->buildViolation('Max time span is 7 days')
                                        ->addViolation();
                                }
                            }
                        }),
                    ],
                ]
            )
            ->add('code', ChoiceType::class, [
                'placeholder' => 'Choose an currency',
                'choices' => [
                    'euro' => 'eur',
                    'dolar' => 'usd',
                    'frank' => 'chf',
                ],
            ])
            ->add('send', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rawData = $form->getData();
            $data = $Nbpapi->getNbpapiTableData($rawData);
            $responseData = $data['responseData'];
            $responseDataHeaders = $data['responseDataHeaders'];
        }

        return $this->render('nbpapi/index.html.twig', [
            'form' => $form,
            'responseData' => $responseData,
            'responseDataHeaders' => $responseDataHeaders,
        ]);
    }
}
