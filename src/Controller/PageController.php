<?php

namespace App\Controller;

use App\Entity\CulturalContent;
use App\Entity\Evenement;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Service\EmailService;
use App\Service\PdfGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/');
    }

    #[Route('/a-propos', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    #[Route('/evenements', name: 'app_events')]
    public function events(EntityManagerInterface $entityManager): Response
    {
        $evenements = $entityManager->getRepository(Evenement::class)->findBy([], ['startDate' => 'ASC']);

        return $this->render('pages/events.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/lieux', name: 'app_places')]
    public function places(EntityManagerInterface $entityManager): Response
    {
        $places = $entityManager->getRepository(CulturalContent::class)->findAll();

        return $this->render('pages/places.html.twig', [
            'places' => $places,
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('pages/contact.html.twig');
    }

    #[Route('/devenir-membre', name: 'app_membership')]
    public function membership(): Response
    {
        // Build countries list using Symfony Intl if available
        $countries = [];
        if (class_exists(\Symfony\Component\Intl\Countries::class)) {
            $codes = \Symfony\Component\Intl\Countries::getCountryCodes();
            foreach ($codes as $code) {
                $countries[] = [
                    'code' => $code,
                    'name' => \Symfony\Component\Intl\Countries::getName($code, 'fr') ?? $code,
                ];
            }
            usort($countries, fn($a, $b) => strcmp($a['name'], $b['name']));
        } else {
            // Fallback minimal list; recommend installing symfony/intl for full list
            $countries = [
                ['code' => 'BJ', 'name' => 'Bénin'],
                ['code' => 'NG', 'name' => 'Nigéria'],
                ['code' => 'FR', 'name' => 'France'],
                ['code' => 'US', 'name' => 'États-Unis'],
            ];
        }

        // Get FedaPay public key from environment
        $fedapayPublicKey = $_ENV['FEDAPAY_PUBLIC_KEY'] ?? $_SERVER['FEDAPAY_PUBLIC_KEY'] ?? null;

        return $this->render('pages/membership.html.twig', [
            'countries' => $countries,
            'fedapay_public_key' => $fedapayPublicKey,
        ]);
    }

    // src/Controller/MembershipController.php

    #[Route('/check-email', name: 'app_check_email', methods: ['POST'])]
    public function checkEmail(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $email = trim($request->request->get('email', ''));
        if (!$email) {
            return new JsonResponse(['ok' => false, 'message' => 'Email manquant'], 400);
        }

        $exists = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($exists) {
            return new JsonResponse(['ok' => false, 'message' => 'Cet email est déjà utilisé.']);
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/avantages', name: 'app_avantage')]
    public function avantage(Request $request, EntityManagerInterface $em, PdfGeneratorService $pdfGeneratorService, EmailService $emailService): Response
    {
        // Récupérer les lieux touristiques depuis la base de données
        $places = $em->getRepository(CulturalContent::class)->findBy([], ['createdAt' => 'DESC'], 6);

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {


            $em->persist($reservation);
            //generer le pdf du facture proforma
            $timestamp = (new \DateTime())->format('YmdHis');
            $filename = 'facture_proforma_' . ($reservation->getId() ?: $timestamp) . '.pdf';
          //empecher de generer la carte si la soumission est pas valid ou a ete deja soumise
          if ($reservation->getFacturepdf() !== null) {
            $this->addFlash('warning', 'Facture proforma deja generée.');
            return $this->redirectToRoute('app_avantage');
          }
            $pdfPath = $pdfGeneratorService->generatePdf(
                'reservation/facture_proforma.html.twig',
                ['reservation' => $reservation],
                $filename,
                'A3',
                'landscape'
            );

            // Extract relative path for database storage (from /pdf/ onwards)
            $relativePath = strstr($pdfPath, '/pdf/');
            if ($relativePath === false) {
                $relativePath = '/pdf/' . $filename;
            }

            $reservation->setFacturepdf($relativePath);
           

            $em->flush();

            // Envoyer le PDF par email
            $emailService->sendReservationConfirmation($reservation, $pdfPath);

            $this->addFlash('success', 'Facture proforma générée et envoyée par email avec succès.');

        $this->redirectToRoute('app_home');
        }
        return $this->render('pages/avantage.html.twig', [
            'form' => $form->createView(),
            'places' => $places,
        ]);
    }

    //page partenaire
    #[Route('/partenaire', name: 'app_partenaire')]
    public function partenaire(): Response
    {
        return $this->render('pages/partenaire.html.twig');
    }

    // Social Impact page
    #[Route('/impact-social', name: 'app_social_impact')]
    public function socialImpact(): Response
    {
        return $this->render('pages/social_impact.html.twig');
    }

    #[Route('/explorer', name: 'app_explorer')]
    public function explorer(EntityManagerInterface $em, Request $request, PdfGeneratorService $pdfGeneratorService, EmailService $emailService): Response
    {
        // Récupérer les lieux touristiques depuis la base de données
        $places = $em->getRepository(CulturalContent::class)->findBy([], ['createdAt' => 'DESC'], 6);

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {


            $em->persist($reservation);
            //generer le pdf du facture proforma
            $timestamp = (new \DateTime())->format('YmdHis');
            $filename = 'facture_proforma_' . ($reservation->getId() ?: $timestamp) . '.pdf';
          //empecher de generer la carte si la soumission est pas valid ou a ete deja soumise
          if ($reservation->getFacturepdf() !== null) {
            $this->addFlash('warning', 'Facture proforma deja generée.');
            return $this->redirectToRoute('app_avantage');
          }
            $pdfPath = $pdfGeneratorService->generatePdf(
                'reservation/facture_proforma.html.twig',
                ['reservation' => $reservation],
                $filename,
                'A3',
                'landscape'
            );

            // Extract relative path for database storage (from /pdf/ onwards)
            $relativePath = strstr($pdfPath, '/pdf/');
            if ($relativePath === false) {
                $relativePath = '/pdf/' . $filename;
            }

            $reservation->setFacturepdf($relativePath);
           

            $em->flush();

            // Envoyer le PDF par email
            $emailService->sendReservationConfirmation($reservation, $pdfPath);

            $this->addFlash('success', 'Facture proforma générée et envoyée par email avec succès.');

        $this->redirectToRoute('app_home');
        }
        return $this->render('pages/explorer.html.twig', [
            'form' => $form->createView(),
            'places' => $places,
        ]);
    }

    #[Route('/projets/binajia-lab', name: 'app_project_lab')]
    public function projectLab(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Binajia Lab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,'Helvetica Neue',Arial,'Apple Color Emoji','Segoe UI Emoji';margin:0;padding:0;background:#f8fafc;color:#0f172a} .wrap{max-width:960px;margin:0 auto;padding:32px} .card{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:24px} h1{font-size:2rem;margin:0 0 12px;background:linear-gradient(90deg,#eab308,#059669);-webkit-background-clip:text;background-clip:text;color:transparent} h2{font-size:1.25rem;margin:24px 0 8px;color:#0f172a} p{line-height:1.6;color:#334155} ul{padding-left:1.1rem;color:#334155} li{margin:.35rem 0} .btn{display:inline-flex;align-items:center;gap:.5rem;background:#eab308;color:#111827;padding:.7rem 1rem;border-radius:9999px;text-decoration:none;font-weight:700} .btn:hover{background:#f59e0b}</style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>🧪 Binajia Lab</h1>
      <p>Laboratoire d’innovation en écologie, tech et design.</p>
      <p>Binajia Lab est un espace dédié à la créativité, à l’expérimentation et à la co‑création. Nous rassemblons des jeunes créatifs, étudiants, startups et porteurs de projets pour imaginer des solutions concrètes au service de l’Afrique et de ses diasporas.</p>
      <h2>🚀 Ce que nous cultivons</h2>
      <ul>
        <li>🌱 Projets en écologie, agriculture durable, recyclage, énergies propres</li>
        <li>💡 Innovations technologiques: outils numériques, applications, objets connectés</li>
        <li>🎨 Créations en design, graphisme, architecture, mode et artisanat</li>
        <li>🤝 Collaborations entre talents, institutions, entreprises et communautés locales</li>
        <li>📚 Ateliers, hackathons, résidences et formations pour stimuler l’intelligence collective</li>
      </ul>
      <p><strong>🎯 100+ projets innovants</strong> déjà lancés. Binajia Lab accompagne les idées qui transforment, relient et valorisent.</p>
      <p><a class="btn" href="{{ path('app_contact') }}">👉 Découvrir / Participer</a></p>
    </div>
  </div>
</body>
</html>
HTML;
        return new Response($html);
    }

    #[Route('/projets/exchange-programme', name: 'app_project_exchange')]
    public function projectExchange(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Binajia Exchange Programme</title>
  <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,'Helvetica Neue',Arial,'Apple Color Emoji','Segoe UI Emoji';margin:0;padding:0;background:#f8fafc;color:#0f172a} .wrap{max-width:960px;margin:0 auto;padding:32px} .card{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:24px} h1{font-size:2rem;margin:0 0 12px;background:linear-gradient(90deg,#eab308,#059669);-webkit-background-clip:text;background-clip:text;color:transparent} h2{font-size:1.25rem;margin:24px 0 8px;color:#0f172a} p{line-height:1.6;color:#334155} ul{padding-left:1.1rem;color:#334155} li{margin:.35rem 0} .btn{display:inline-flex;align-items:center;gap:.5rem;background:#eab308;color:#111827;padding:.7rem 1rem;border-radius:9999px;text-decoration:none;font-weight:700} .btn:hover{background:#f59e0b}</style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>Binajia Exchange Programme</h1>
      <p>Programme d’échange pour jeunes leaders et artistes.</p>
      <p>Binajia Exchange est un programme dédié aux étudiants, artistes, entrepreneurs et leaders communautaires engagés dans la transformation de l’Afrique et de sa diaspora.</p>
      <p>Nous favorisons les échanges pour :</p>
      <ul>
        <li>Stimuler la coopération entre jeunes acteurs du changement</li>
        <li>Encourager la créativité, le leadership et l’engagement citoyen</li>
        <li>Renforcer les compétences via ateliers, résidences et immersions</li>
        <li>Créer des ponts entre Bénin, Nigéria, diaspora et communautés du monde</li>
      </ul>
      <p><strong>🎯 200+ leaders déjà formés.</strong></p>
      <p><a class="btn" href="{{ path('app_contact') }}">Découvrir / Participer</a></p>
    </div>
  </div>
</body>
</html>
HTML;
        return new Response($html);
    }

    #[Route('/projets/market-connect', name: 'app_project_market')]
    public function projectMarket(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Market Connect</title>
  <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,'Helvetica Neue',Arial,'Apple Color Emoji','Segoe UI Emoji';margin:0;padding:0;background:#f8fafc;color:#0f172a} .wrap{max-width:960px;margin:0 auto;padding:32px} .card{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:24px} h1{font-size:2rem;margin:0 0 12px;background:linear-gradient(90deg,#eab308,#059669);-webkit-background-clip:text;background-clip:text;color:transparent} h2{font-size:1.25rem;margin:24px 0 8px;color:#0f172a} p{line-height:1.6;color:#334155} ul{padding-left:1.1rem;color:#334155} li{margin:.35rem 0} .btn{display:inline-flex;align-items:center;gap:.5rem;background:#eab308;color:#111827;padding:.7rem 1rem;border-radius:9999px;text-decoration:none;font-weight:700} .btn:hover{background:#f59e0b}</style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>🛍️ Market Connect</h1>
      <p>Plateforme de collaboration économique locale, régionale et africaine.</p>
      <h2>🤝 Ce que nous soutenons</h2>
      <ul>
        <li>Femmes entrepreneures bâtissant des solutions durables</li>
        <li>Jeunes porteurs de projets à fort impact</li>
        <li>Artisans et commerçants valorisant les savoir‑faire</li>
        <li>Coopérations régionales et africaines</li>
        <li>Connexions internationales ouvrant les marchés</li>
        <li>Formations, vitrines, marchés et outils numériques</li>
      </ul>
      <p><strong>🎯 1000+ entrepreneurs déjà connectés.</strong></p>
      <p><a class="btn" href="{{ path('app_contact') }}">Découvrir / Adhérer</a></p>
    </div>
  </div>
</body>
</html>
HTML;
        return new Response($html);
    }

    #[Route('/projets/culture-fest', name: 'app_project_culture')]
    public function projectCulture(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Culture Fest</title>
  <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,'Helvetica Neue',Arial,'Apple Color Emoji','Segoe UI Emoji';margin:0;padding:0;background:#f8fafc;color:#0f172a} .wrap{max-width:960px;margin:0 auto;padding:32px} .card{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:24px} h1{font-size:2rem;margin:0 0 12px;background:linear-gradient(90deg,#eab308,#059669);-webkit-background-clip:text;background-clip:text;color:transparent} h2{font-size:1.25rem;margin:24px 0 8px;color:#0f172a} p{line-height:1.6;color:#334155} ul{padding-left:1.1rem;color:#334155} li{margin:.35rem 0} .btn{display:inline-flex;align-items:center;gap:.5rem;background:#eab308;color:#111827;padding:.7rem 1rem;border-radius:9999px;text-decoration:none;font-weight:700} .btn:hover{background:#f59e0b}</style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>🎭 Culture Fest</h1>
      <p>Festival culturel pour le grand public et les artistes.</p>
      <h2>🎉 Ce que nous célébrons</h2>
      <ul>
        <li>🎶 Concerts et performances musicales</li>
        <li>🎭 Parades de masques et rituels vivants</li>
        <li>🍲 Découvertes gastronomiques et ateliers</li>
        <li>🖼️ Expositions, marchés d’art, résidences</li>
        <li>🤝 Rencontres entre publics, créateurs, chercheurs</li>
      </ul>
      <p><strong>🎯 50+ événements par an.</strong></p>
      <p><a class="btn" href="{{ path('app_contact') }}">Découvrir / Programme</a></p>
    </div>
  </div>
</body>
</html>
HTML;
        return new Response($html);
    }

    #[Route('/projets/transit', name: 'app_project_transit')]
    public function projectTransit(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Transit</title>
  <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,'Helvetica Neue',Arial,'Apple Color Emoji','Segoe UI Emoji';margin:0;padding:0;background:#f8fafc;color:#0f172a} .wrap{max-width:960px;margin:0 auto;padding:32px} .card{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.06);padding:24px} h1{font-size:2rem;margin:0 0 12px;background:linear-gradient(90deg,#eab308,#059669);-webkit-background-clip:text;background-clip:text;color:transparent} h2{font-size:1.25rem;margin:24px 0 8px;color:#0f172a} p{line-height:1.6;color:#334155} ul{padding-left:1.1rem;color:#334155} li{margin:.35rem 0} .btn{display:inline-flex;align-items:center;gap:.5rem;background:#eab308;color:#111827;padding:.7rem 1rem;border-radius:9999px;text-decoration:none;font-weight:700} .btn:hover{background:#f59e0b}</style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>🚚 Transit</h1>
      <p>Faciliter la libre circulation des personnes, des savoirs et des biens entre le Bénin, le Nigéria et l’ensemble du territoire africain.</p>
      <h2>🌍 Nos axes d’action</h2>
      <ul>
        <li>🛂 Promouvoir la libre circulation entre pays africains</li>
        <li>📦 Faciliter le transit des biens et marchandises (cadres légaux et douaniers)</li>
        <li>📚 Valoriser les savoirs et compétences locales</li>
        <li>🤝 Renforcer les liens entre institutions, entrepreneurs et citoyens</li>
        <li>🧭 Créer des corridors économiques et culturels africains</li>
      </ul>
      <p><strong>🎯 Transit – Pour une Afrique qui circule, qui échange et qui se relie.</strong></p>
      <p><a class="btn" href="{{ path('app_contact') }}">Découvrir / Collaborer</a></p>
    </div>
  </div>
</body>
</html>
HTML;
        return new Response($html);
    }
}
