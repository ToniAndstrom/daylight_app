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
                $daylightData = $this->calculateDaylightChanges($coordinates);

                // Fetch sunrise and sunset times
                $sunTimes = $this->getSunriseSunsetTimes($coordinates);

                // Calculate the time left for sunset if current time is before sunset
                $now = new \DateTime();
                $sunset = new \DateTime($sunTimes["sunset"]);
                if ($now < $sunset) {
                    $timeLeftForSunset = $now
                        ->diff($sunset)
                        ->format("%h hours %i minutes left for today's sunset");
                } else {
                    $timeLeftForSunset = "Sunset has passed.";
                }

                // Render the results with the daylight data
                return $this->render("city/show.html.twig", [
                    "form" => $form->createView(),
                    "daylightChanges" => $daylightData["daylightChanges"],
                    "cityName" => $cityName,
                    "sunrise" => $daylightData["sunriseLocal"],
                    "sunset" => $daylightData["sunsetLocal"],
                    "time_left_for_sunset" =>
                        $daylightData["timeLeftForSunset"], // This is in UTC
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
        $apiKey = $this->getParameter("geocode_api_key"); // Use the 'geocode_api_key' parameter

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

    // Add a new method to fetch sunrise and sunset times
    private function getSunriseSunsetTimes($coordinates): ?array
    {
        $response = $this->client->request(
            "GET",
            "https://api.sunrise-sunset.org/json",
            [
                "query" => [
                    "lat" => $coordinates["lat"],
                    "lng" => $coordinates["lng"],
                    "date" => "today",
                    "formatted" => 0, // Get the time in ISO 8601 format
                ],
            ]
        );

        $data = $response->toArray();

        if (!empty($data["results"])) {
            // Create DateTime objects from the API response
            $sunriseUtc = new \DateTime(
                $data["results"]["sunrise"],
                new \DateTimeZone("UTC")
            );
            $sunsetUtc = new \DateTime(
                $data["results"]["sunset"],
                new \DateTimeZone("UTC")
            );

            // Set the time zone to the local time zone of Espoo, Uusimaa, Finland
            $localTimeZone = new \DateTimeZone("Europe/Helsinki"); // Helsinki is the capital city of Finland and shares the same time zone as Espoo

            // Convert the times to the local time zone
            $sunriseUtc->setTimezone($localTimeZone);
            $sunsetUtc->setTimezone($localTimeZone);

            // Format the times to your preference
            $sunriseLocal = $sunriseUtc->format("H:i:s");
            $sunsetLocal = $sunsetUtc->format("H:i:s");

            return [
                "sunrise" => $sunriseLocal,
                "sunset" => $sunsetLocal,
            ];
        }
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
            date("Y-m-d"), // Add today's date to the array
        ];

        $sunriseLocal = "";
        $sunsetLocal = "";
        $timeLeftForSunset = ""; // Initialize the variable

        // $daylightChanges["time_left_for_sunset"] =
        //     "Time left for sunset is not available.";

        foreach ($datesToCheck as $date) {
            $daylightData = $this->fetchDaylightData($coordinates, $date);

            if (!empty($daylightData["results"])) {
                // Convert sunrise and sunset to local time zone
                $sunriseLocal = (new \DateTime(
                    $daylightData["results"]["sunrise"],
                    new \DateTimeZone("UTC")
                ))
                    ->setTimezone(new \DateTimeZone("Europe/Helsinki"))
                    ->format("H:i:s");
                $sunsetLocal = (new \DateTime(
                    $daylightData["results"]["sunset"],
                    new \DateTimeZone("UTC")
                ))
                    ->setTimezone(new \DateTimeZone("Europe/Helsinki"))
                    ->format("H:i:s");

                $dayLength = (new \DateTime($sunsetLocal))->diff(
                    new \DateTime($sunriseLocal)
                );
                $daylightChanges[$date] = $dayLength->format(
                    "%h hours %i minutes"
                );

                // Calculate the time left for sunset in UTC
                if ($date == date("Y-m-d")) {
                    $nowUtc = new \DateTime("now", new \DateTimeZone("UTC"));
                    $sunsetUtc = new \DateTime(
                        $daylightData["results"]["sunset"],
                        new \DateTimeZone("UTC")
                    );
                    if ($nowUtc < $sunsetUtc) {
                        $timeLeft = $nowUtc->diff($sunsetUtc);
                        $timeLeftForSunset = $timeLeft->format(
                            "%h hours %i minutes left for today's sunset"
                        );
                    } else {
                    // Calculate how many minutes ago sunset occurred
                        $timeSinceSunset = $sunsetUtc->diff($nowUtc);
                        $minutesSinceSunset = $timeSinceSunset->days * 24 * 60;
                        $minutesSinceSunset += $timeSinceSunset->h * 60;
                        $minutesSinceSunset += $timeSinceSunset->i;
                        $daylightChanges['time_left_for_sunset'] = 'Sunset has passed ' . $minutesSinceSunset . ' minutes ago.';
                    }
                }
            }
        }
        // Return the daylight changes along with the local sunrise and sunset times
        return [
            "daylightChanges" => $daylightChanges,
            "sunriseLocal" => $sunriseLocal,
            "sunsetLocal" => $sunsetLocal,
            "timeLeftForSunset" => $timeLeftForSunset, // Ensure this key is always set
        ];
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