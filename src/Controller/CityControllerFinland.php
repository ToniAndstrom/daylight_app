<?php

namespace App\Controller;

use App\Form\CityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class CityControllerFinland extends AbstractController
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route("/", name: "daylight")]
    public function index(Request $request): Response
    {
        $form = $this->createForm(CityType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cityName = $form->get("city")->getData();

            // Fetch latitude and longitude for the entered city name
            $coordinates = $this->getCoordinatesForCity($cityName);

            if ($coordinates) {
                // Calculate the change in daylight length in minutes
                $daylightChanges = $this->calculateDaylightChanges(
                    $coordinates
                );

                // Render the results with the daylight data
                return $this->render("city/show.html.twig", [
                    "form" => $form->createView(),
                    "daylightChanges" => $daylightChanges,
                    "cityName" => $cityName,
                ]);
            } else {
                // Handle the case where coordinates could not be found
                $this->addFlash(
                    "error",
                    "Could not find the coordinates for the entered city. Please try a different city."
                );
            }
        }

        return $this->render("city/index.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    #[Route("/api/daylight/{cityName}", name: "api_daylight")]
    public function daylightApi(
        Request $request,
        string $cityName
    ): JsonResponse {
        // No need to manually set $cityName, it's now obtained from the route parameter

        $coordinates = $this->getCoordinatesForCity($cityName);

        if ($coordinates) {
            $daylightChanges = $this->calculateDaylightChanges($coordinates);

            return $this->json([
                "daylightChanges" => $daylightChanges,
                "cityName" => $cityName,
            ]);
        } else {
            return $this->json(
                [
                    "error" =>
                        "Could not find the coordinates for the entered city.",
                ],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private function getCoordinatesForCity($cityName): ?array
    {
        $apiKey = $this->getParameter('geocode_api_key'); // Use the 'geocode_api_key' parameter
        
        $geocodeResponse = $this->client->request(
            "GET",
            "https://geocode.maps.co/search",
            [
                "query" => [
                    "q" => $cityName,
                    "api_key" => $apiKey, // Use the API key from the .env file
                ],
            ]
        );

        $geocodeData = $geocodeResponse->toArray();

        if (
            !empty($geocodeData) &&
            isset($geocodeData[0]["lat"], $geocodeData[0]["lon"])
        ) {
            return [
                "lat" => $geocodeData[0]["lat"],
                "lng" => $geocodeData[0]["lon"],
            ];
        }

        return null;
    }

    private function calculateDaylightChanges($coordinates): array
    {
        $daylightChanges = [];
        $datesToCheck = [
            "2024-01-01",
            "2024-02-01",
            "2024-03-01",
            "2024-04-01",
            "2024-05-01",
            "2024-06-01",
            "2024-07-01",
            "2024-08-01",
            "2024-09-01",
            "2024-10-01",
            "2024-11-01",
            "2024-12-01",
        ];

        foreach ($datesToCheck as $date) {
            $daylightData = $this->fetchDaylightData($coordinates, $date);

            if (!empty($daylightData["results"])) {
                $sunrise = new \DateTime($daylightData["results"]["sunrise"]);
                $sunset = new \DateTime($daylightData["results"]["sunset"]);
                $dayLength = $sunset->diff($sunrise);

                $daylightChanges[$date] = $dayLength->format(
                    "%h hours %i minutes"
                );
            }
        }

        return $daylightChanges;
    }

    private function fetchDaylightData($coordinates, $date): array
    {
        $response = $this->client->request(
            "GET",
            "https://api.sunrise-sunset.org/json",
            [
                "query" => [
                    "lat" => $coordinates["lat"],
                    "lng" => $coordinates["lng"],
                    "date" => $date,
                    "formatted" => 0,
                ],
            ]
        );

        return $response->toArray();
    }
}