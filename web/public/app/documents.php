<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/helpers.php';

require_login();
$user = current_user();

$docs = [];
try {
    $docs = db()->query(
        'SELECT * FROM admin_documents WHERE public_visible = 1 ORDER BY created_at DESC'
    )->fetchAll();
} catch (Throwable) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documents &amp; Reports – 3Commas</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen pb-24 md:pb-6">

  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 px-4 py-3 flex items-center gap-3 md:hidden">
    <a href="profile.php" class="text-slate-500 hover:text-slate-700 transition">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <span class="text-lg font-extrabold text-emerald-400">Documents &amp; Reports</span>
  </header>

  <?php $activePage = 'profile.php'; include '_nav.php'; ?>

  <!-- Legal Information Modals -->
  <?php
  $legalDocs = [
    ['id' => 'rpp_from',       'title' => 'Recurrent Payment Policy as from December 29th 2024'],
    ['id' => 'rpp_until',      'title' => 'Recurrent Payment Policy until December 28th 2024'],
    ['id' => 'tor_from',       'title' => 'Terms of Referral as from December 29th 2024'],
    ['id' => 'tor_until',      'title' => 'Terms of Referral until December 28th 2024'],
    ['id' => 'api_from',       'title' => 'API Terms of Use for Developers as from December 29th 2024'],
    ['id' => 'api_until',      'title' => 'API Terms of Use for Developers until December 28th 2024'],
    ['id' => 'sp_from',        'title' => 'Signal Providers Terms of Service as from December 29th 2024'],
    ['id' => 'sp_until',       'title' => 'Signal Providers Terms of Service until December 28th 2024'],
    ['id' => 'bb_from',        'title' => 'Bug Bounty as from December 29th 2024'],
    ['id' => 'bb_until',       'title' => 'Bug Bounty until December 28th 2024'],
    ['id' => 'cp_from',        'title' => 'Complaint Procedure as from December 29th 2024'],
    ['id' => 'cp_until',       'title' => 'Complaint Procedure until December 28th 2024'],
    ['id' => 'aff_from_2025',  'title' => 'Affiliate Program Terms and Conditions as from November 10th 2025'],
    ['id' => 'aff_until_2025', 'title' => 'Affiliate Program Terms and Conditions until November 9th 2025'],
    ['id' => 'aff_until_2024', 'title' => 'Affiliate Program Terms and Conditions until December 28th 2024'],
    ['id' => 'cpa_from',       'title' => 'Affiliate CPA Program Terms and Conditions as from December 29th 2024'],
    ['id' => 'cpa_until',      'title' => 'Affiliate CPA Program Terms and Conditions until December 28th 2024'],
    ['id' => 'gdpr',           'title' => 'GDPR Statement'],
    ['id' => 'refund',         'title' => 'Refund Policy'],
  ];

  $legalContents = [
    'rpp_from'       => '<p class="text-xs text-slate-400 mb-3 italic">This Recurring Payment Policy is effective as of December 29, 2024. For definitions of capitalized terms, refer to the Terms of Use.</p>
<p><strong>1.</strong> For issues related to payments or refunds, consult the 3Commas Help Center. To request assistance, contact 3Commas Support through the "Contact Us"/"Support" form or email support[at]3commas.io.</p>
<p><strong>2. Authorization.</strong> By agreeing to this policy, you authorize 3C Trade Tech Ltd. (BVI company number: 2164568, address: Geneva Place, 2nd Floor, #333 Waterfront Drive, Road Town Tortola, British Virgin Islands) or 3Commas Technologies OÜ (registration code 14125515, address: Laeva 2, Tallinn, Estonia, 10412) on behalf of 3C Trade Tech Ltd. (each separately "3Commas") to charge your specified default payment method, whether it\'s a Visa, Mastercard, or other payment card, or your PayPal account (including linked cards and bank accounts) every 30 days (also referred to as \'monthly\' or \'on a month-to-month basis\') or every year (also referred to as \'annual\' or \'on a year-to-year basis\'), as chosen when subscribing.</p>
<p><strong>3. Payment Processing:</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Payments made via our merchant record, Paddle.com, will be processed by them. Your relationship with Paddle is governed by the Paddle Checkout Buyer Terms and Conditions.</li>
  <li>Payments made through Apple AppStore or Google Play are processed by the respective platforms. Your relationship with Apple is governed by Apple Media Services Terms and Conditions. Your relationship with Google is governed by Google Play Terms of Service.</li>
</ul>
<p><strong>4. Acceptance.</strong> By agreeing to this policy, you confirm:</p>
<ul class="list-disc pl-5 space-y-1">
  <li>Prior to each subscription purchase, you will be informed about the payment amount, frequency, and the first payment date.</li>
  <li>You are not under a jurisdiction prohibiting the use of Visa, Mastercard, PayPal, Paddle, or other payment institutions.</li>
  <li>You provided correct and full payment information to 3Commas.</li>
  <li>You have authorized 3Commas to conduct recurring transactions on your behalf.</li>
  <li>You accept responsibility for all recurring payment obligations until the subscription\'s cancellation.</li>
</ul>
<p><strong>5. Payment Method Verification.</strong> You confirm that your specified payment method:</p>
<ul class="list-disc pl-5 space-y-1">
  <li>If the payment card is legally owned by you or you have rights to use it on behalf of an entity.</li>
  <li>If PayPal is an account registered by you or you have the right to use it on behalf of an entity.</li>
  <li>Has adequate credit/funds.</li>
  <li>Is operational and not compromised.</li>
</ul>
<p><strong>6. Automatic Charges.</strong> Payments will be processed on the due date or within a few days thereafter. Initially, you\'ll be notified about upcoming transactions 4 days in advance. A second notification will be sent 2 days prior to the charge. Notifications will be issued by our merchant record, Paddle.com.</p>
<p><strong>7. Payment Failures.</strong> In case of payment failure due to reasons like insufficient funds, card expiration, or account suspension, we\'ll reattempt as follows:</p>
<ul class="list-disc pl-5 space-y-1">
  <li>For general payments and payments via our merchant record Paddle: We\'ll retry after three days.</li>
  <li>For AppStore: We\'ll retry after sixteen days.</li>
  <li>For Google Play: We\'ll retry after seven days.</li>
</ul>
<p>After three consecutive failed attempts within these time frames, recurring payments will be automatically canceled. Access to the Software\'s paid features might require a new subscription for activation.</p>
<p><strong>8. Cancellation.</strong> You can cancel your Software subscription anytime via the Software, Apple, or Google interface. No refunds will be issued for the current subscription period, and the final active day will be the day before the next payment date.</p>',
    'rpp_until'      => '<p class="text-xs text-slate-400 mb-3 italic">This Recurring Payment Policy is effective as of December 10, 2023. For definitions of capitalized terms, refer to the Terms of Use.</p>
<p><strong>1.</strong> For issues related to payments or refunds, consult the 3Commas Help Center. To request assistance, contact 3Commas Support through the "Contact Us"/"Support" form or email support[at]3commas.io.</p>
<p><strong>2. Authorization.</strong> By agreeing to this policy, you authorize 3Commas Technologies OÜ ("3Commas") to charge your specified default payment method, whether it\'s a Visa, Mastercard, or other payment card, or your PayPal account (including linked cards and bank accounts) every 30 days (also referred to as \'monthly\' or \'on a month-to-month basis\') or every year (also referred to as \'annual\' or \'on a year-to-year basis\'), as chosen when subscribing.</p>
<p><strong>3. Payment Processing:</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Payments made via our merchant record, Paddle.com, will be processed by them. Your relationship with Paddle is governed by the Paddle Checkout Buyer Terms and Conditions.</li>
  <li>Payments made through Apple AppStore or Google Play are processed by the respective platforms. Your relationship with Apple is governed by Apple Media Services Terms and Conditions. Your relationship with Google is governed by Google Play Terms of Service.</li>
</ul>
<p><strong>4. Acceptance.</strong> By agreeing to this policy, you confirm:</p>
<ul class="list-disc pl-5 space-y-1">
  <li>Prior to each subscription purchase, you will be informed about the payment amount, frequency, and the first payment date.</li>
  <li>You are not under a jurisdiction prohibiting the use of Visa, Mastercard, PayPal, Paddle, or other payment institutions.</li>
  <li>You provided correct and full payment information to 3Commas.</li>
  <li>You have authorized 3Commas to conduct recurring transactions on your behalf.</li>
  <li>You accept responsibility for all recurring payment obligations until the subscription\'s cancellation.</li>
</ul>
<p><strong>5. Payment Method Verification.</strong> You confirm that your specified payment method:</p>
<ul class="list-disc pl-5 space-y-1">
  <li>If the payment card is legally owned by you or you have rights to use it on behalf of an entity.</li>
  <li>If PayPal is an account registered by you or you have the right to use it on behalf of an entity.</li>
  <li>Has adequate credit/funds.</li>
  <li>Is operational and not compromised.</li>
</ul>
<p><strong>6. Automatic Charges.</strong> Payments will be processed on the due date or within a few days thereafter. Initially, you\'ll be notified about upcoming transactions 4 days in advance. A second notification will be sent 2 days prior to the charge. Notifications will be issued by our merchant record, Paddle.com.</p>
<p><strong>7. Payment Failures.</strong> In case of payment failure due to reasons like insufficient funds, card expiration, or account suspension, we\'ll reattempt as follows:</p>
<ul class="list-disc pl-5 space-y-1">
  <li>For general payments and payments via our merchant record Paddle: We\'ll retry after three days.</li>
  <li>For AppStore: We\'ll retry after sixteen days.</li>
  <li>For Google Play: We\'ll retry after seven days.</li>
</ul>
<p>After three consecutive failed attempts within these time frames, recurring payments will be automatically canceled. Access to the Software\'s paid features might require a new subscription for activation.</p>
<p><strong>8. Cancellation.</strong> You can cancel your Software subscription anytime via the Software, Apple, or Google interface. No refunds will be issued for the current subscription period, and the final active day will be the day before the next payment date.</p>',
    'tor_from'       => '<p class="text-xs text-slate-400 mb-3 italic">These Terms of Referral are effective as of 29 December 2024.</p>
<p>3Commas team manages online software as a service including website located at https://3commas.io, 3Commas mobile application(s), application program interface(s), all together or each separately referred to as the "Software", and provides online services and technical support.</p>
<p>3Commas team is represented by 3C Trade Tech Ltd., a company incorporated under the laws of the British Virgin Islands with BVI company number 2164568 and registered address at Geneva Place, 2nd Floor, #333 Waterfront Drive, Road Town Tortola, British Virgin Islands ("3Commas", "we", "us" or "our").</p>
<p>These Terms of Referral ("Referral Terms") govern your ("you" or the "Client") actions in promoting the proliferation of the Software. In matters not regulated herein, the Client Terms of Use shall apply.</p>
<p class="font-semibold">BY CREATING AND/OR SHARING THE REFERRAL LINK YOU ACCEPT AND ACKNOWLEDGE BEING BOUND BY THE REFERRAL TERMS.</p>
<p><strong>1. DEFINITIONS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li><strong>1.1 "Referrer"</strong> – a Client Account holder not banned/blocked, who has accepted these terms and participates in the referral program. Legal entities require manual approval by 3Commas.</li>
  <li><strong>1.2 "Referral Link"</strong> – an automatically generated hyperlink to the Software registration scenario, personalized for the Referrer.</li>
  <li><strong>1.3 "Promo Code"</strong> – an automatically generated code personalized for the Referrer to attract new Qualified Clients.</li>
  <li><strong>1.4 "Referrer Account"</strong> – a separately displayed account available to the Referrer upon acceptance of these terms, accounted in USD equivalent.</li>
  <li><strong>1.5 "Qualified Client"</strong> – a person/entity who (a) registered using the Referral Link or Promo Code, (b) purchased and paid for a Subscription in acceptable cryptocurrency or fiat (fiat only if Referrer is a legal entity), and (c) is not the Referrer herself.</li>
</ul>
<p><strong>2. OBLIGATIONS OF THE REFERRER</strong></p>
<p><strong>2.1</strong> To become a Referrer you must: (a) be a registered Client with a completely filled profile; (b) complete AML/KYC verification if applicable; (c) if acting on behalf of a legal entity, obtain manual approval from 3Commas; (d) obtain the Referral Link from the "Invite Friends" section of the Software.</p>
<p><strong>2.2</strong> The Referrer may attract any person ensuring they are of legal age, not in a prohibited jurisdiction, and aware that use of the Software is at their own discretion and responsibility.</p>
<p><strong>2.3</strong> The Referrer shall NOT: enter into agreements on behalf of 3Commas; introduce themselves as an employee/partner of 3Commas; collude with other Clients for illegal benefit; make statements or warranties about the Software; harm 3Commas\'s reputation; misuse 3Commas intellectual property.</p>
<p><strong>2.4</strong> A new client is a Qualified Client only if they registered via the Referral Link and paid for a Subscription in acceptable cryptocurrency or fiat.</p>
<p><strong>3. PAYMENT TERMS</strong></p>
<p><strong>3.1</strong> For a natural person Referrer, fees are calculated as a percentage of net successful cryptocurrency payments:</p>
<ul class="list-disc pl-5 space-y-1">
  <li>Level 1 Qualified Client (attracted by Referrer): <strong>25%</strong></li>
  <li>Level 2 Qualified Client (attracted by Level 1): <strong>15%</strong></li>
  <li>Level 3 Qualified Client (attracted by Level 2): <strong>10%</strong></li>
</ul>
<p><strong>3.2</strong> For a legal entity Referrer, the fee percentage is agreed between the Referrer and 3Commas upon approval of the application.</p>
<p><strong>3.3</strong> 3Commas pays fees in USDC cryptocurrency. 3Commas may require the Referrer to submit an invoice prior to payment.</p>
<p><strong>3.4</strong> The Referrer\'s fee is credited to the Referrer Account each month proportionally to payments made by the Qualified Client, converted to USD equivalent at the 3Commas rate at the time of crediting.</p>
<p><strong>3.5</strong> 3Commas is not obliged to transfer fees for payments outside the scope of these terms or transfer funds in currencies other than acceptable cryptocurrency.</p>
<p><strong>3.6</strong> With funds on the Referrer Account, you may: (a) credit them to your Client Account (usable only for 3Commas Subscriptions, non-withdrawable); or (b) withdraw to an external wallet subject to Software limitations.</p>
<p><strong>3.7</strong> The Referrer is solely responsible for all taxes, charges and levies on fees received.</p>
<p><strong>4. SANCTIONS COMPLIANCE</strong></p>
<p><strong>4.1</strong> By using 3Commas services, you represent that you are not on any trade embargo or economic sanctions lists (including EU, UN, BVI, OFAC, U.S. Commerce Department, and UK OFSI lists), that your use does not violate international sanctions, and that you are not from a sanctioned country or region.</p>
<p><strong>4.2</strong> 3Commas reserves the right to restrict or refuse services in certain countries or regions at its sole discretion.</p>
<p><strong>4.3</strong> If you become subject to international sanctions, you must immediately stop using our services and notify us.</p>
<p><strong>4.4</strong> 3Commas may terminate, suspend, or restrict services if you become subject to sanctions, if providing services would violate sanctions, if you are assessed as related to a sanctioned territory or person, or per Section 4.2.</p>',
    'tor_until'      => '<p class="text-xs text-slate-400 mb-3 italic">These Terms of Referral are effective as of 13 October 2023.</p>
<p>3Commas team manages online software as a service including website located at https://3commas.io, 3Commas mobile application(s), application program interface(s), all together or each separately referred to as the "Software", and provides online services and technical support.</p>
<p>3Commas team is represented by 3Commas Technologies OÜ, a limited liability company incorporated under the laws of the Republic of Estonia with registration code 14125515 and registered address at Laeva tn 2, Kesklinna linnaosa, Tallinn, Harju maakond, 10111 ("3Commas", "we", "us" or "our").</p>
<p>These Terms of Referral ("Referral Terms") govern your ("you" or the "Client") actions in promoting the proliferation of the Software. In matters not regulated herein, the Client Terms of Use shall apply.</p>
<p class="font-semibold">BY CREATING AND/OR SHARING THE REFERRAL LINK YOU ACCEPT AND ACKNOWLEDGE BEING BOUND BY THE REFERRAL TERMS.</p>
<p><strong>1. DEFINITIONS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li><strong>1.1 "Referrer"</strong> – a Client Account holder not banned/blocked, who has accepted these terms and participates in the referral program. Legal entities require manual approval by 3Commas.</li>
  <li><strong>1.2 "Referral Link"</strong> – an automatically generated hyperlink to the Software registration scenario, personalized for the Referrer.</li>
  <li><strong>1.3 "Promo Code"</strong> – an automatically generated code personalized for the Referrer to attract new Qualified Clients.</li>
  <li><strong>1.4 "Referrer Account"</strong> – a separately displayed account available to the Referrer upon acceptance of these terms, accounted in USD equivalent.</li>
  <li><strong>1.5 "Qualified Client"</strong> – a person/entity who (a) registered using the Referral Link or Promo Code, (b) purchased and paid for a Subscription in acceptable cryptocurrency (USDT, ETH or other listed) or fiat (fiat only if Referrer is a legal entity), and (c) is not the Referrer herself.</li>
</ul>
<p><strong>2. OBLIGATIONS OF THE REFERRER</strong></p>
<p><strong>2.1</strong> To become a Referrer you must: (a) be a registered Client with a completely filled profile; (b) complete AML/KYC verification if applicable; (c) if acting on behalf of a legal entity, obtain manual approval from 3Commas; (d) obtain the Referral Link from the "Invite Friends" section of the Software.</p>
<p><strong>2.2</strong> The Referrer may attract any person ensuring they are of legal age, not in a prohibited jurisdiction, and aware that use of the Software is at their own discretion and responsibility.</p>
<p><strong>2.3</strong> The Referrer shall NOT: enter into agreements on behalf of 3Commas; introduce themselves as an employee/partner of 3Commas; collude with other Clients for illegal benefit; make statements or warranties about the Software; harm 3Commas\'s reputation; misuse 3Commas intellectual property.</p>
<p><strong>2.4</strong> A new client is a Qualified Client only if they registered via the Referral Link and paid for a Subscription in acceptable cryptocurrency (USDT, ETH or other listed) or fiat.</p>
<p><strong>3. PAYMENT TERMS</strong></p>
<p><strong>3.1</strong> For a natural person Referrer, fees are calculated as a percentage of net successful cryptocurrency payments:</p>
<ul class="list-disc pl-5 space-y-1">
  <li>Level 1 Qualified Client (attracted by Referrer): <strong>25%</strong></li>
  <li>Level 2 Qualified Client (attracted by Level 1): <strong>15%</strong></li>
  <li>Level 3 Qualified Client (attracted by Level 2): <strong>10%</strong></li>
</ul>
<p><strong>3.2</strong> For a legal entity Referrer, the fee percentage is agreed between the Referrer and 3Commas upon approval of the application.</p>
<p><strong>3.3</strong> 3Commas pays fees in USDT cryptocurrency. 3Commas may require the Referrer to submit an invoice prior to payment.</p>
<p><strong>3.4</strong> The Referrer\'s fee is credited to the Referrer Account each month proportionally to payments made by the Qualified Client, converted to USD equivalent at the 3Commas rate at the time of crediting.</p>
<p><strong>3.5</strong> 3Commas is not obliged to transfer fees for payments outside the scope of these terms or transfer funds in currencies other than acceptable cryptocurrency.</p>
<p><strong>3.6</strong> With funds on the Referrer Account, you may: (a) credit them to your Client Account (usable only for 3Commas Subscriptions, non-withdrawable); or (b) withdraw to an external wallet subject to Software limitations.</p>
<p><strong>3.7</strong> The Referrer is solely responsible for all taxes, charges and levies on fees received.</p>
<p><strong>4. SANCTIONS COMPLIANCE</strong></p>
<p><strong>4.1</strong> By using 3Commas services, you represent that you are not on any trade embargo or economic sanctions lists (including EU, UN, Estonia, OFAC, U.S. Commerce Department, and UK OFSI lists), that your use does not violate international sanctions, and that you are not from a sanctioned country or region.</p>
<p><strong>4.2</strong> 3Commas reserves the right to restrict or refuse services in certain countries or regions at its sole discretion.</p>
<p><strong>4.3</strong> If you become subject to international sanctions, you must immediately stop using our services and notify us.</p>
<p><strong>4.4</strong> 3Commas may terminate, suspend, or restrict services if you become subject to sanctions, if providing services would violate sanctions, if you are assessed as related to a sanctioned territory or person, or per Section 4.2.</p>',
    'api_from'       => '<p class="text-xs text-slate-400 mb-3 italic">These Terms of Use are effective as of 29 December 2024.</p>
<p><strong>1. DEFINITIONS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li><strong>1.1</strong> The 3Commas API is made available by 3C Trade Tech Ltd., a company formed under the laws of the British Virgin Islands ("3Commas", "we", "us" or "our") through https://3commas.io.</li>
  <li><strong>1.2 "Application"</strong> – the specialized program developed/integrated by you using our API.</li>
  <li><strong>1.3 "Terms of Service" / "API Terms"</strong> – these 3Commas API Terms of Service.</li>
  <li><strong>1.4 "End-User(s)"</strong> – a person who ultimately uses or is intended to use our API or other 3Commas products.</li>
</ul>
<p><strong>2. SCOPE</strong></p>
<p>These API Terms, together with related specification documents, our Terms of Use, and Privacy Notice form a binding "Contract" between you and us. BY ACCEPTING THESE TERMS, you confirm you have read and agreed to be bound by them, assume all obligations herein, are of sufficient legal age, are not in a prohibited jurisdiction, and use the API at your own discretion and responsibility.</p>
<p>If entering into these Terms on behalf of a legal entity, you represent you have authority to bind such entity.</p>
<p><strong>3. INTELLECTUAL PROPERTY RIGHTS AND REQUIREMENTS</strong></p>
<p><strong>3.1</strong> 3Commas grants you a limited, non-exclusive, non-assignable, non-transferable, revocable license to use our API to develop, test, and support your Application. Violation of these Terms may result in suspension or termination.</p>
<p><strong>3.2</strong> You may NOT: impair or damage 3Commas or its services; disrupt or gain unauthorized access to services/networks via the API; reverse engineer, decompile or disassemble the API; exploit or publicly disclose security vulnerabilities; violate any applicable law (including copyright, privacy laws, or laws protecting minors).</p>
<p><strong>3.3</strong> For all content/data you insert or make available via our API, you grant 3Commas a free, transferable, sublicensable, non-exclusive, irrevocable, worldwide right to use such content for any purpose including providing the API, research, analytics, product development, and commercial use. You guarantee content complies with these API Terms and does not violate third-party rights.</p>
<p><strong>3.4</strong> Further requirements are included in related specification documents. In case of conflict, specification documents shall prevail.</p>
<p><strong>4. USE OF API</strong></p>
<p><strong>4.1</strong> You must comply with all laws applicable to data accessed through our API, including GDPR and privacy regulations.</p>
<p><strong>4.2</strong> For data obtained through the API you must: obtain all necessary consents; keep locally stored data up to date; implement proper retention/deletion policies; and maintain a written privacy statement available to End Users.</p>
<p><strong>4.3</strong> It is forbidden to manipulate any information or functionalities connected to End User accounts without notifying and obtaining express consent from the affected End User beforehand.</p>
<p><strong>4.4</strong> You must notify End Users of any changes to the Application. End Users must give express consent prior to any manipulation.</p>
<p><strong>4.5</strong> It is forbidden to use the API or connected Applications to offer investment advice of any kind.</p>
<p><strong>5. SUSPENSION OF USE</strong></p>
<p>In case of breach we may immediately and without notice suspend your use of the API. We will monitor Applications for compliance. You must not interfere with such monitoring and must assist us in verifying compliance upon request.</p>
<p><strong>6. CHANGES TO TERMS</strong></p>
<p>We may update these API Terms. Material changes will be communicated via email 30 days in advance. If you do not agree, you may terminate use of the API within those 30 days.</p>
<p><strong>7. LIABILITY AND WARRANTIES</strong></p>
<p>THE API IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. WE EXCLUDE ALL IMPLIED WARRANTIES TO THE EXTENT PERMITTED BY LAW. YOU AGREE TO INDEMNIFY AND HOLD HARMLESS 3Commas AND ITS AFFILIATES FROM ANY CLAIMS ARISING FROM YOUR BREACH OF THESE API TERMS OR YOUR APPLICATION(S).</p>
<p><strong>8. TERMINATION</strong></p>
<p>Either party may terminate with 30 days\' written notice. In case of fundamental breach, we may suspend or terminate immediately at our sole discretion.</p>
<p><strong>9. PRICES, PAYMENT TERMS AND REFUNDS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Payouts are made once per calendar month based on your request in your 3Commas profile.</li>
  <li>No commission is charged on the first USD 1,000 generated from subscriptions to your Application.</li>
  <li>After that threshold: <strong>10% commission</strong> on monthly income.</li>
  <li>Six months after reaching the threshold: <strong>30% commission</strong> on monthly income.</li>
  <li>You are solely responsible for all applicable taxes.</li>
</ul>
<p><strong>10. PRIVACY</strong></p>
<p>To use the API you must provide certain Personal Data. 3Commas will collect and use it as described in our Privacy Notice at https://3commas.io/privacy-policy. Questions may be sent to support[at]3commas.io.</p>
<p><strong>11. GENERAL</strong></p>
<p>The API is intended for businesses, not consumers. You may not assign your rights hereunder. These API Terms are governed by British Virgin Islands law and disputes settled in relevant BVI courts.</p>
<p><strong>12. SANCTIONS COMPLIANCE</strong></p>
<p>By using 3Commas services, you represent you are not on any sanctions list (EU, UN, BVI, OFAC, U.S. Commerce, UK OFSI), your use does not violate international sanctions, and you are not from a sanctioned country. If you become subject to sanctions, you must immediately stop using our services and notify us. 3Commas may terminate or restrict services accordingly.</p>
<p class="text-xs text-slate-400 mt-3">3C Trade Tech Ltd. · Geneva Place, 2nd Floor, #333 Waterfront Drive, Road Town Tortola, British Virgin Islands · BVI company number: 2164568</p>',
    'api_until'      => '<p class="text-xs text-slate-400 mb-3 italic">These Terms of Use are effective as of December 10, 2023.</p>
<p><strong>1. DEFINITIONS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li><strong>1.1</strong> The 3Commas API is made available by 3Commas Technologies OÜ, a company formed under the laws of the Republic of Estonia ("3Commas", "we", "us" or "our") through https://3commas.io.</li>
  <li><strong>1.2 "Application"</strong> – the specialized program developed/integrated by you using our API.</li>
  <li><strong>1.3 "Terms of Service" / "API Terms"</strong> – these 3Commas API Terms of Service.</li>
  <li><strong>1.4 "End-User(s)"</strong> – a person who ultimately uses or is intended to use our API or other 3Commas products.</li>
</ul>
<p><strong>2. SCOPE</strong></p>
<p>These API Terms, together with related specification documents, our Terms of Use, and Privacy Notice form a binding "Contract" between you and us. BY ACCEPTING THESE TERMS, you confirm you have read and agreed to be bound by them, assume all obligations herein, are of sufficient legal age, are not in a prohibited jurisdiction, and use the API at your own discretion and responsibility.</p>
<p>If entering into these Terms on behalf of a legal entity, you represent you have authority to bind such entity.</p>
<p><strong>3. INTELLECTUAL PROPERTY RIGHTS AND REQUIREMENTS</strong></p>
<p><strong>3.1</strong> 3Commas grants you a limited, non-exclusive, non-assignable, non-transferable, revocable license to use our API to develop, test, and support your Application. Violation of these Terms may result in suspension or termination.</p>
<p><strong>3.2</strong> You may NOT: impair or damage 3Commas or its services; disrupt or gain unauthorized access to services/networks via the API; reverse engineer, decompile or disassemble the API; exploit or publicly disclose security vulnerabilities; violate any applicable law (including copyright, privacy laws, or laws protecting minors).</p>
<p><strong>3.3</strong> For all content/data you insert or make available via our API, you grant 3Commas a free, transferable, sublicensable, non-exclusive, irrevocable, worldwide right to use such content for any purpose including providing the API, research, analytics, product development, and commercial use. You guarantee content complies with these API Terms and does not violate third-party rights.</p>
<p><strong>3.4</strong> Further requirements are included in related specification documents. In case of conflict, specification documents shall prevail.</p>
<p><strong>4. USE OF API</strong></p>
<p><strong>4.1</strong> You must comply with all laws applicable to data accessed through our API, including GDPR and privacy regulations.</p>
<p><strong>4.2</strong> For data obtained through the API you must: obtain all necessary consents; keep locally stored data up to date; implement proper retention/deletion policies; and maintain a written privacy statement available to End Users.</p>
<p><strong>4.3</strong> It is forbidden to manipulate any information or functionalities connected to End User accounts without notifying and obtaining express consent from the affected End User beforehand.</p>
<p><strong>4.4</strong> You must notify End Users of any changes to the Application and obtain their express consent prior to any manipulation.</p>
<p><strong>4.5</strong> It is forbidden to use the API or connected Applications to offer investment advice of any kind.</p>
<p><strong>5. SUSPENSION OF USE</strong></p>
<p>In case of breach we may immediately and without notice suspend your use of the API. We will monitor Applications for compliance. You must not interfere with such monitoring and must assist us in verifying compliance upon request.</p>
<p><strong>6. CHANGES TO TERMS</strong></p>
<p>We may update these API Terms. Material changes will be communicated via email 30 days in advance. If you do not agree, you may terminate use of the API within those 30 days.</p>
<p><strong>7. LIABILITY AND WARRANTIES</strong></p>
<p>THE API IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. WE EXCLUDE ALL IMPLIED WARRANTIES TO THE EXTENT PERMITTED BY LAW. YOU AGREE TO INDEMNIFY AND HOLD HARMLESS 3Commas AND ITS AFFILIATES FROM ANY CLAIMS ARISING FROM YOUR BREACH OF THESE API TERMS OR YOUR APPLICATION(S).</p>
<p><strong>8. TERMINATION</strong></p>
<p>Either party may terminate with 30 days\' written notice. In case of fundamental breach, we may suspend or terminate immediately at our sole discretion.</p>
<p><strong>9. PRICES, PAYMENT TERMS AND REFUNDS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Payouts are made once per calendar month based on your request in your 3Commas profile.</li>
  <li>No commission is charged on the first USD 1,000 generated from subscriptions to your Application.</li>
  <li>After that threshold: <strong>10% commission</strong> on monthly income.</li>
  <li>Six months after reaching the threshold: <strong>30% commission</strong> on monthly income.</li>
  <li>You are solely responsible for all applicable taxes.</li>
</ul>
<p><strong>10. PRIVACY</strong></p>
<p>To use the API you must provide certain Personal Data. 3Commas will collect and use it as described in our Privacy Notice at https://3commas.io/privacy-policy. Questions may be sent to support[at]3commas.io.</p>
<p><strong>11. GENERAL</strong></p>
<p>The API is intended for businesses, not consumers. You may not assign your rights hereunder. These API Terms are governed by Estonian law and disputes settled in Harju County Court (Estonia).</p>
<p><strong>12. SANCTIONS COMPLIANCE</strong></p>
<p>By using 3Commas services, you represent you are not on any sanctions list (EU, UN, Estonia, OFAC, U.S. Commerce, UK OFSI), your use does not violate international sanctions, and you are not from a sanctioned country. If you become subject to sanctions, you must immediately stop using our services and notify us. 3Commas may terminate or restrict services accordingly.</p>
<p class="text-xs text-slate-400 mt-3">3Commas Technologies OÜ · Laeva 2, Tallinn, Estonia, 10412 · Registration code: 14125515 · VAT: EE101951896</p>',
    'sp_from'        => '<p class="text-xs text-slate-400 mb-3 italic">These Terms of Use are effective as of December 29, 2024.</p>
<p><strong>1. DEFINITIONS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li><strong>1.1 "Signal(s)"</strong> – notifications from you to subscribed bots providing information about which coin to buy or sell, acting as a trigger for a bot to trade a crypto pair using your private strategies and algorithms.</li>
  <li><strong>1.2 "Subscriber(s)"</strong> – a person or legal entity who subscribes to your bot template and receives Signals.</li>
  <li><strong>1.3 "Software"</strong> – the service made available by 3C Trade Tech Ltd. (BVI) through https://3commas.io, including mobile apps, APIs, and the Marketplace service.</li>
</ul>
<p><strong>2. SCOPE</strong></p>
<p>These Signallers Terms and the Privacy Notice constitute the entire agreement between you and 3Commas regarding the Software. By accepting, you confirm you have read and are bound by these terms, are of sufficient legal age, are not in a prohibited jurisdiction, and use the Software at your own responsibility. If acting on behalf of a legal entity, you represent you have authority to bind that entity.</p>
<p><strong>3. SANCTIONS COMPLIANCE</strong></p>
<p>By using the Software, you represent you are not on any sanctions list (EU, UN, BVI, OFAC, U.S. Commerce, UK OFSI), your use does not violate international sanctions, and you are not from a sanctioned country or region. If you become subject to sanctions, you must immediately stop using our services and notify us. 3Commas may terminate or restrict services accordingly.</p>
<p><strong>4. INTELLECTUAL PROPERTY AND LICENSE</strong></p>
<p>3Commas grants you a limited, non-exclusive, non-assignable, non-transferable, revocable license to use the Software for personal, non-commercial use during the term of these terms. You may not rent, sell, reverse engineer, decompile, modify, or create derivative works based on the Software. For all Content you insert via the Software, you grant 3Commas a free, worldwide, irrevocable right to use it for any purpose including providing the Software, research, analytics, and commercial use.</p>
<p><strong>5. SIGN-UP</strong></p>
<p>To provide Signals you must be at least 18 years old, have a 3Commas account, and submit an application to become a Signal provider. You are solely responsible for ensuring that use of the Software and provision of Signals is permitted by applicable laws in your jurisdiction.</p>
<p><strong>6. USE OF SOFTWARE AND PROVISION OF SIGNALS</strong></p>
<p><strong>6.1</strong> By providing Signals, you conclude an independent agreement with each Subscriber and are responsible for any refund requests from your Subscribers. You accept responsibility for the quality of your Signals.</p>
<p><strong>6.2</strong> Ranking on the 3Commas marketplace is based on: signals that are new to the marketplace; signals offered at a discount or free; and signals offered exclusively on the 3Commas marketplace.</p>
<p><strong>6.3</strong> You must comply with all laws applicable to data accessed through the Software, including GDPR.</p>
<p><strong>6.4</strong> Signals may only be provided in accordance with these terms. Deviations may be deemed a material breach at 3Commas\' sole discretion.</p>
<p><strong>6.5</strong> Material breaches (resulting in immediate removal) include: sending scam/fake signals (e.g. signals after an artificial pump); copying signals from other groups; abusing the 3Commas Software; providing signals in violation of applicable laws.</p>
<p><strong>7. COMMISSIONS AND REFUNDS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>You receive <strong>70%</strong> of every subscription amount per month (net of refunds); 3Commas retains <strong>30% commission</strong>.</li>
  <li>Submit your payout request by the 27th of the month; 3Commas pays by the 7th of the following month.</li>
  <li>Payment is made in <strong>USDC</strong>. You are solely responsible for all applicable taxes.</li>
  <li>3Commas may propose a different commission percentage prior to accepting your application, agreed in writing.</li>
</ul>
<p><strong>8. SUSPENSION</strong></p>
<p>3Commas may suspend or interrupt your use of the Software and provision of Signals without liability if: your actions harm the Software or other users; complaints are received about your Signals; maintenance is required; your credentials are compromised; you breach these terms; you refuse to provide required clarifications; or for any other reason at 3Commas\' discretion. 3Commas will endeavour to notify you in advance where possible.</p>
<p><strong>9. TERM AND TERMINATION</strong></p>
<p>These terms are for an indefinite term. Either party may terminate with 30 days\' notice. Upon notice, 3Commas will stop accepting new subscribers to your Signals. Both parties must continue fulfilling obligations during the notice period. Post-termination, 3Commas retains Subscriber data per the Privacy Notice; you will have no access to Subscriber information.</p>
<p><strong>10. DISCLAIMER</strong></p>
<p>3COMMAS PROVIDES THE SOFTWARE ONLY. 3COMMAS DOES NOT PROVIDE FINANCIAL, INVESTMENT, LEGAL, TAX OR ANY OTHER PROFESSIONAL ADVICE. NOTHING IN THE SOFTWARE CONSTITUTES INVESTMENT ADVICE OR RECOMMENDATIONS. YOU EXPRESSLY AGREE THAT USE OF THE SOFTWARE AND PROVISION OF SIGNALS IS AT YOUR SOLE RISK.</p>
<p><strong>11. LIABILITY AND WARRANTIES</strong></p>
<p>THE SOFTWARE IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. TO THE MAXIMUM EXTENT PERMITTED BY LAW, 3Commas IS NOT LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, OR CONSEQUENTIAL DAMAGES ARISING FROM YOUR USE OF THE SOFTWARE. 3Commas maximum aggregate liability is limited to fees paid by you in the 12 months preceding any claim.</p>
<p><strong>12. INDEMNIFICATION</strong></p>
<p>You agree to indemnify and hold harmless 3Commas and its affiliates from any claims arising from your breach of these terms and/or your provision of Signals to Subscribers.</p>
<p><strong>13. CHANGES TO TERMS</strong></p>
<p>3Commas may update these terms. Material changes will be communicated via email 30 days in advance. If you do not agree, you may terminate within those 30 days.</p>
<p><strong>14. PRIVACY</strong></p>
<p>To provide Signals you must provide certain Personal Data. 3Commas will collect and use it as described in the Privacy Notice at https://3commas.io/privacy-policy. Questions may be sent to support[at]3commas.io.</p>
<p><strong>15. GENERAL</strong></p>
<p>These terms are governed by British Virgin Islands law and disputes settled in relevant BVI courts. 3Commas may transfer its rights and obligations to a third party with advance notice; you may terminate immediately if you do not agree to the transfer.</p>
<p class="text-xs text-slate-400 mt-3">3C Trade Tech Ltd. · Geneva Place, 2nd Floor, #333 Waterfront Drive, Road Town Tortola, British Virgin Islands · BVI company number: 2164568</p>',
    'sp_until'       => '<p class="text-xs text-slate-400 mb-3 italic">These Terms of Use are effective as of December 10, 2023.</p>
<p><strong>1. DEFINITIONS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li><strong>1.1 "Signal(s)"</strong> – notifications from you to subscribed bots providing information about which coin to buy or sell, acting as a trigger for a bot to trade a crypto pair using your private strategies and algorithms.</li>
  <li><strong>1.2 "Subscriber(s)"</strong> – a person or legal entity who subscribes to your bot template and receives Signals.</li>
  <li><strong>1.3 "Software"</strong> – the service made available by 3Commas Technologies OÜ (Estonia) through https://3commas.io, including mobile apps, APIs, and the Marketplace service.</li>
</ul>
<p><strong>2. SCOPE</strong></p>
<p>These Signallers Terms and the Privacy Notice constitute the entire agreement between you and 3Commas regarding the Software. By accepting, you confirm you have read and are bound by these terms, are of sufficient legal age, are not in a prohibited jurisdiction, and use the Software at your own responsibility. If acting on behalf of a legal entity, you represent you have authority to bind that entity.</p>
<p><strong>3. SANCTIONS COMPLIANCE</strong></p>
<p>By using the Software, you represent you are not on any sanctions list (EU, UN, Estonia, OFAC, U.S. Commerce, UK OFSI), your use does not violate international sanctions applicable in the Republic of Estonia, and you are not from a sanctioned country or region. If you become subject to sanctions, you must immediately stop using our services and notify us. 3Commas may terminate or restrict services accordingly.</p>
<p><strong>4. INTELLECTUAL PROPERTY AND LICENSE</strong></p>
<p>3Commas grants you a limited, non-exclusive, non-assignable, non-transferable, revocable license to use the Software for personal, non-commercial use during the term of these terms. You may not rent, sell, reverse engineer, decompile, modify, or create derivative works based on the Software. For all Content you insert via the Software, you grant 3Commas a free, worldwide, irrevocable right to use it for any purpose including providing the Software, research, analytics, and commercial use.</p>
<p><strong>5. SIGN-UP</strong></p>
<p>To provide Signals you must be at least 18 years old, have a 3Commas account, and submit an application to become a Signal provider. You are solely responsible for ensuring that use of the Software and provision of Signals is permitted by applicable laws in your jurisdiction.</p>
<p><strong>6. USE OF SOFTWARE AND PROVISION OF SIGNALS</strong></p>
<p><strong>6.1</strong> By providing Signals, you conclude an independent agreement with each Subscriber and are responsible for any refund requests. You accept responsibility for the quality of your Signals.</p>
<p><strong>6.2</strong> Ranking on the 3Commas marketplace is based on: signals that are new to the marketplace; signals offered at a discount or free; and signals offered exclusively on the 3Commas marketplace.</p>
<p><strong>6.3</strong> You must comply with all laws applicable to data accessed through the Software, including GDPR.</p>
<p><strong>6.4</strong> Signals may only be provided in accordance with these terms. Deviations may be deemed a material breach at 3Commas\' sole discretion.</p>
<p><strong>6.5</strong> Material breaches (resulting in immediate removal) include: sending scam/fake signals (e.g. signals after an artificial pump); copying signals from other groups; abusing the 3Commas Software; providing signals in violation of applicable laws.</p>
<p><strong>7. COMMISSIONS AND REFUNDS</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>You receive <strong>70%</strong> of every subscription amount per month (net of refunds); 3Commas retains <strong>30% commission</strong>.</li>
  <li>Submit your payout request by the 27th of the month; 3Commas pays by the 7th of the following month.</li>
  <li>Payment is made in <strong>USDT</strong>. You are solely responsible for all applicable taxes.</li>
  <li>3Commas may propose a different commission percentage prior to accepting your application, agreed in writing.</li>
</ul>
<p><strong>8. SUSPENSION</strong></p>
<p>3Commas may suspend or interrupt your use of the Software and provision of Signals without liability if: your actions harm the Software or other users; complaints are received about your Signals; maintenance is required; your credentials are compromised; you breach these terms; you refuse to provide required clarifications; or for any other reason at 3Commas\' discretion. 3Commas will endeavour to notify you in advance where possible.</p>
<p><strong>9. TERM AND TERMINATION</strong></p>
<p>These terms are for an indefinite term. Either party may terminate with 30 days\' notice. Upon notice, 3Commas will stop accepting new subscribers to your Signals. Both parties must continue fulfilling obligations during the notice period. Post-termination, 3Commas retains Subscriber data per the Privacy Notice; you will have no access to Subscriber information.</p>
<p><strong>10. DISCLAIMER</strong></p>
<p>3COMMAS PROVIDES THE SOFTWARE ONLY AND DOES NOT PROVIDE FINANCIAL, INVESTMENT, LEGAL, OR TAX ADVICE. NOTHING IN THE SOFTWARE CONSTITUTES INVESTMENT ADVICE OR RECOMMENDATIONS. YOU EXPRESSLY AGREE THAT USE OF THE SOFTWARE AND PROVISION OF SIGNALS IS AT YOUR SOLE RISK.</p>
<p><strong>11. LIABILITY AND WARRANTIES</strong></p>
<p>THE SOFTWARE IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. TO THE MAXIMUM EXTENT PERMITTED BY LAW, 3Commas IS NOT LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, OR CONSEQUENTIAL DAMAGES ARISING FROM YOUR USE OF THE SOFTWARE. 3Commas maximum aggregate liability is limited to fees paid by you in the 12 months preceding any claim.</p>
<p><strong>12. INDEMNIFICATION</strong></p>
<p>You agree to indemnify and hold harmless 3Commas and its affiliates from any claims arising from your breach of these terms and/or your provision of Signals to Subscribers.</p>
<p><strong>13. CHANGES TO TERMS</strong></p>
<p>3Commas may update these terms. Material changes will be communicated via email 30 days in advance. If you do not agree, you may terminate within those 30 days.</p>
<p><strong>14. PRIVACY</strong></p>
<p>To provide Signals you must provide certain Personal Data. 3Commas will collect and use it as described in the Privacy Notice at https://3commas.io/privacy-policy. Questions may be sent to support[at]3commas.io.</p>
<p><strong>15. GENERAL</strong></p>
<p>These terms are governed by Estonian law and disputes settled in Harju County Court (Estonia). 3Commas may transfer its rights and obligations to a third party with advance notice; you may terminate immediately if you do not agree to the transfer.</p>
<p class="text-xs text-slate-400 mt-3">3Commas Technologies OÜ · Laeva 2, Tallinn, Estonia, 10412 · Registration code: 14125515 · VAT: EE101951896</p>',
    'bb_from'        => '<p class="text-xs text-slate-400 mb-3 italic">This Bug Bounty is effective as of 29 December 2024.</p>
<p>Thank you for your interest in our Bug Bounty program and helping us make our platform stronger and safer. If you discover a bug, we welcome your cooperation in responsibly investigating and reporting it to us.</p>
<p><strong>1. INTRODUCTION</strong></p>
<p><strong>1.1 Purpose:</strong> To leverage the expertise of security researchers to identify and report vulnerabilities in our systems, applications, and network infrastructure.</p>
<p><strong>1.2 Scope — In Scope:</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Customer Cabinet: https://app.3commas.io/</li>
  <li>API Interactions: https://api.3commas.io/</li>
  <li>iOS Application: https://apps.apple.com/app/id6446649413</li>
  <li>Android Application: https://play.google.com/store/apps/details?id=io.threecommas.wallet</li>
</ul>
<p><strong>Excluded from scope:</strong> https://feedback.3commas.io/ (3rd party feedback portal), https://careers.3commas.io/ (careers portal), https://3commas.tech/ (experimental project).</p>
<p><strong>1.3 Key Definitions:</strong> Bug Bounty Program, Security Researcher, Confidentiality, NDA, Duplicate Report, Vulnerability, CVSS, Proof of Concept (PoC), Responsible Disclosure — standard industry definitions apply. CVSS v3.1 is used for scoring.</p>
<p><strong>2. PROGRAM OVERVIEW</strong></p>
<p>The program is open to all eligible security researchers. Participants must: obtain explicit authorisation before testing; respect the defined scope; report vulnerabilities promptly and responsibly; refrain from actions that could cause harm or disrupt services; and adhere to responsible disclosure principles.</p>
<p><strong>3. ELIGIBILITY</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Must be at least 18 years old.</li>
  <li>Must not be an employee, contractor, or affiliate of our Company.</li>
  <li>Must not be prohibited by law or regulations from participating (no sanctions, etc.).</li>
  <li>Must comply with all applicable laws and regulations.</li>
</ul>
<p><strong>4. VULNERABILITY SUBMISSION PROCESS</strong></p>
<p>Submit a detailed report including: description of the vulnerability; steps to reproduce; affected system/version; proof-of-concept (required for medium severity and above). Reports are submitted via https://3commas.zapier.app. We acknowledge receipt within 2 business days and provide an initial assessment within 10 business days.</p>
<p><strong>5. SEVERITY LEVELS AND REWARDS</strong></p>
<table class="w-full text-xs border-collapse mt-2 mb-2">
  <thead><tr class="bg-slate-100"><th class="border border-slate-200 px-2 py-1 text-left">Severity</th><th class="border border-slate-200 px-2 py-1 text-left">CVSS Score</th><th class="border border-slate-200 px-2 py-1 text-left">Min</th><th class="border border-slate-200 px-2 py-1 text-left">Max</th></tr></thead>
  <tbody>
    <tr><td class="border border-slate-200 px-2 py-1">Low</td><td class="border border-slate-200 px-2 py-1">0.1–3.9</td><td class="border border-slate-200 px-2 py-1">50 USDC</td><td class="border border-slate-200 px-2 py-1">200 USDC</td></tr>
    <tr><td class="border border-slate-200 px-2 py-1">Medium</td><td class="border border-slate-200 px-2 py-1">4.0–6.9</td><td class="border border-slate-200 px-2 py-1">200 USDC</td><td class="border border-slate-200 px-2 py-1">1,000 USDC</td></tr>
    <tr><td class="border border-slate-200 px-2 py-1">High</td><td class="border border-slate-200 px-2 py-1">7.0–8.9</td><td class="border border-slate-200 px-2 py-1">1,000 USDC</td><td class="border border-slate-200 px-2 py-1">2,500 USDC</td></tr>
    <tr><td class="border border-slate-200 px-2 py-1">Critical</td><td class="border border-slate-200 px-2 py-1">9.0–10.0</td><td class="border border-slate-200 px-2 py-1">2,500 USDC</td><td class="border border-slate-200 px-2 py-1">5,000 USDC</td></tr>
  </tbody>
</table>
<p>Actual payout within the range is at the sole discretion of the Program Administration. Provide your TRC20 wallet address for reward payment.</p>
<p><strong>6. LEGAL AND COMPLIANCE</strong></p>
<p>Participants must comply with all applicable laws. All tax obligations on rewards are the sole responsibility of the participant. Vulnerability data is handled per our Privacy Notice at https://3commas.io/privacy-notice. Participants must not access, exfiltrate, or tamper with personal data belonging to others.</p>
<p><strong>Sanctions:</strong> By participating, you represent you are not on any sanctions list (EU, UN, BVI, OFAC, U.S. Commerce, UK OFSI), your participation does not violate international sanctions, and you are not from a sanctioned country. If you become subject to sanctions, you must immediately stop and notify us.</p>
<p><strong>7. RESPONSIBLE DISCLOSURE</strong></p>
<p>Do not publicly disclose any vulnerability before receiving approval from program administrators. Allow reasonable time for remediation. Participants who report valid vulnerabilities receive acknowledgement, potential swag, and (with consent) public recognition.</p>
<p><strong>8. ISSUES EXCLUDED FROM SCOPE</strong> (reports will not be accepted):</p>
<ul class="list-disc pl-5 space-y-1 text-xs">
  <li>Clickjacking / UI redressing with minimal security impact</li>
  <li>CSRF on unauthenticated or non-sensitive forms</li>
  <li>Application-level crashes without exploit; MITM/SQLi mentions without PoC</li>
  <li>Known vulnerable libraries without working PoC</li>
  <li>Missing best practices in SSL/TLS, CSP, cookies, email (SPF/DKIM/DMARC)</li>
  <li>DoS / rate limiting on non-authentication endpoints</li>
  <li>Content spoofing without demonstrated attack vector</li>
  <li>Outdated browser vulnerabilities, software version disclosure, benign information disclosure</li>
  <li>Tabnabbing, self-XSS, non-technical attacks (phishing, social engineering)</li>
  <li>Session invalidation issues, password/email policy issues</li>
  <li>Blind SSRF without proven business impact</li>
  <li>Broken link hijacking (unless directly embedded in frontend code)</li>
  <li>Vulnerabilities requiring MITM, physical access, jailbroken/rooted environment</li>
  <li>Arbitrary file upload, internal IP/domain exposure, path disclosure</li>
  <li>Auth app secret in APK, binary reverse engineering deficiencies</li>
  <li>Reports from automated scans without working PoCs</li>
  <li>GitHub token/key mentions without proof of production use</li>
  <li>Documentation errors</li>
</ul>
<p class="text-xs text-slate-400 mt-3">Contact: https://3commas.zapier.app · 3C Trade Tech Ltd. · Geneva Place, 2nd Floor, #333 Waterfront Drive, Road Town Tortola, British Virgin Islands · BVI company number: 2164568</p>',
    'bb_until'       => '<p class="text-xs text-slate-400 mb-3 italic">This Bug Bounty is effective as of April 12, 2024.</p>
<p>Thank you for your interest in our Bug Bounty program and helping us make our platform stronger and safer. If you discover a bug, we welcome your cooperation in responsibly investigating and reporting it to us.</p>
<p><strong>1. INTRODUCTION</strong></p>
<p><strong>1.1 Purpose:</strong> To leverage the expertise of security researchers to identify and report vulnerabilities in our systems, applications, and network infrastructure.</p>
<p><strong>1.2 Scope — In Scope:</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Customer Cabinet: https://app.3commas.io/</li>
  <li>API Interactions: https://api.3commas.io/</li>
  <li>iOS Application: https://apps.apple.com/app/id6446649413</li>
  <li>Android Application: https://play.google.com/store/apps/details?id=io.threecommas.wallet</li>
</ul>
<p><strong>Excluded from scope:</strong> https://feedback.3commas.io/ (3rd party feedback portal), https://careers.3commas.io/ (careers portal), https://3commas.tech/ (experimental project).</p>
<p><strong>1.3 Key Definitions:</strong> Bug Bounty Program, Security Researcher, Confidentiality, NDA, Duplicate Report, Vulnerability, CVSS, Proof of Concept (PoC), Responsible Disclosure — standard industry definitions apply. CVSS v3.1 is used for scoring.</p>
<p><strong>2. PROGRAM OVERVIEW</strong></p>
<p>The program is open to all eligible security researchers. Participants must: obtain explicit authorisation before testing; respect the defined scope; report vulnerabilities promptly and responsibly; refrain from actions that could cause harm or disrupt services; and adhere to responsible disclosure principles.</p>
<p><strong>3. ELIGIBILITY</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Must be at least 18 years old.</li>
  <li>Must not be an employee, contractor, or affiliate of our Company.</li>
  <li>Must not be prohibited by law or regulations from participating (no sanctions, etc.).</li>
  <li>Must comply with all applicable laws and regulations.</li>
</ul>
<p><strong>4. VULNERABILITY SUBMISSION PROCESS</strong></p>
<p>Submit a detailed report including: description of the vulnerability; steps to reproduce; affected system/version; proof-of-concept (required for medium severity and above). Reports are submitted via https://3commas.zapier.app. We acknowledge receipt within 2 business days and provide an initial assessment within 10 business days.</p>
<p><strong>5. SEVERITY LEVELS AND REWARDS</strong></p>
<table class="w-full text-xs border-collapse mt-2 mb-2">
  <thead><tr class="bg-slate-100"><th class="border border-slate-200 px-2 py-1 text-left">Severity</th><th class="border border-slate-200 px-2 py-1 text-left">CVSS Score</th><th class="border border-slate-200 px-2 py-1 text-left">Min</th><th class="border border-slate-200 px-2 py-1 text-left">Max</th></tr></thead>
  <tbody>
    <tr><td class="border border-slate-200 px-2 py-1">Low</td><td class="border border-slate-200 px-2 py-1">0.1–3.9</td><td class="border border-slate-200 px-2 py-1">50 USDT</td><td class="border border-slate-200 px-2 py-1">200 USDT</td></tr>
    <tr><td class="border border-slate-200 px-2 py-1">Medium</td><td class="border border-slate-200 px-2 py-1">4.0–6.9</td><td class="border border-slate-200 px-2 py-1">200 USDT</td><td class="border border-slate-200 px-2 py-1">1,000 USDT</td></tr>
    <tr><td class="border border-slate-200 px-2 py-1">High</td><td class="border border-slate-200 px-2 py-1">7.0–8.9</td><td class="border border-slate-200 px-2 py-1">1,000 USDT</td><td class="border border-slate-200 px-2 py-1">2,500 USDT</td></tr>
    <tr><td class="border border-slate-200 px-2 py-1">Critical</td><td class="border border-slate-200 px-2 py-1">9.0–10.0</td><td class="border border-slate-200 px-2 py-1">2,500 USDT</td><td class="border border-slate-200 px-2 py-1">5,000 USDT</td></tr>
  </tbody>
</table>
<p>Actual payout within the range is at the sole discretion of the Program Administration. Provide your TRC20 wallet address for reward payment.</p>
<p><strong>6. LEGAL AND COMPLIANCE</strong></p>
<p>Participants must comply with all applicable laws. All tax obligations on rewards are the sole responsibility of the participant. Vulnerability data is handled per our Privacy Notice at https://3commas.io/privacy-notice. Participants must not access, exfiltrate, or tamper with personal data belonging to others.</p>
<p><strong>Sanctions:</strong> By participating, you represent you are not on any sanctions list (EU, UN, Estonia, OFAC, U.S. Commerce, UK OFSI), your participation does not violate international sanctions applicable in the Republic of Estonia, and you are not from a sanctioned country. If you become subject to sanctions, you must immediately stop and notify us.</p>
<p><strong>7. RESPONSIBLE DISCLOSURE</strong></p>
<p>Do not publicly disclose any vulnerability before receiving approval from program administrators. Allow reasonable time for remediation. Participants who report valid vulnerabilities receive acknowledgement, potential swag, and (with consent) public recognition.</p>
<p><strong>8. ISSUES EXCLUDED FROM SCOPE</strong> (reports will not be accepted):</p>
<ul class="list-disc pl-5 space-y-1 text-xs">
  <li>Clickjacking / UI redressing with minimal security impact</li>
  <li>CSRF on unauthenticated or non-sensitive forms</li>
  <li>Application-level crashes without exploit; MITM/SQLi mentions without PoC</li>
  <li>Known vulnerable libraries without working PoC</li>
  <li>Missing best practices in SSL/TLS, CSP, cookies, email (SPF/DKIM/DMARC)</li>
  <li>DoS / rate limiting on non-authentication endpoints</li>
  <li>Content spoofing without demonstrated attack vector</li>
  <li>Outdated browser vulnerabilities, software version disclosure, benign information disclosure</li>
  <li>Tabnabbing, self-XSS, non-technical attacks (phishing, social engineering)</li>
  <li>Session invalidation issues, password/email policy issues</li>
  <li>Blind SSRF without proven business impact</li>
  <li>Broken link hijacking (unless directly embedded in frontend code)</li>
  <li>Vulnerabilities requiring MITM, physical access, jailbroken/rooted environment</li>
  <li>Arbitrary file upload, internal IP/domain exposure, path disclosure</li>
  <li>Auth app secret in APK, binary reverse engineering deficiencies</li>
  <li>Reports from automated scans without working PoCs</li>
  <li>GitHub token/key mentions without proof of production use</li>
  <li>Documentation errors</li>
</ul>
<p class="text-xs text-slate-400 mt-3">Contact: https://3commas.zapier.app · 3Commas Technologies OÜ · Laeva 2, Tallinn, Estonia, 10412 · Registration code: 14125515</p>',
    'cp_from'        => '<p class="text-xs text-slate-400 mb-3 italic">This Complaint Procedure is effective as of 29 December 2024.</p>
<p><strong>PURPOSE</strong></p>
<p>This Complaint Procedure informs you about how to contact us regarding any complaint about the services we provide, how we will address your complaint, and what options are available should you not be satisfied with our response.</p>
<p><strong>HOW TO CONTACT US</strong></p>
<p>Contact our Customer Service Team by email: complaints[at]3commas.io</p>
<p><strong>HOW TO MAKE A COMPLAINT</strong></p>
<p>To register an official complaint, send an email to complaints[at]3commas.io with a detailed description of your issue. The more detail you provide, the faster we can respond — otherwise we may need to request additional information.</p>
<p>After submitting a complaint, you will receive an acknowledgement within <strong>72 hours</strong> of us receiving it.</p>
<p><strong>RESPONSE TIME</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Our Customer Service Team will use best efforts to resolve your matter promptly.</li>
  <li>A final decision will be given within <strong>10 business days</strong>. If the complaint cannot be resolved within this period, we may extend by an additional 10 business days — you will be informed of the extension and the reasons why.</li>
  <li>If your complaint requires engagement of a third party, the final decision will be given within <strong>8 weeks</strong>.</li>
  <li>For complaints related to personal data processing, we follow the GDPR timeframe and respond within <strong>30 days</strong>.</li>
</ul>
<p>If you are not satisfied with our response regarding Personal Data, or believe we are processing your Personal Data unlawfully, you may submit a claim to the data protection supervisory authority of the country of your residence.</p>',
    'cp_until'       => '<p><strong>PURPOSE</strong></p>
<p>This Complaint Procedure informs you about how to contact us regarding any complaint about the services we provide, how we will address your complaint, and what options are available should you not be satisfied with our response.</p>
<p><strong>HOW TO CONTACT US</strong></p>
<p>Contact our Customer Service Team by email: complaints[at]3commas.io</p>
<p><strong>HOW TO MAKE A COMPLAINT</strong></p>
<p>To register an official complaint, send an email to complaints[at]3commas.io with a detailed description of your issue. The more detail you provide, the faster we can respond — otherwise we may need to request additional information.</p>
<p>After submitting a complaint, you will receive an acknowledgement within <strong>72 hours</strong> of us receiving it.</p>
<p><strong>RESPONSE TIME</strong></p>
<ul class="list-disc pl-5 space-y-1">
  <li>Our Customer Service Team will use best efforts to resolve your matter promptly.</li>
  <li>A final decision will be given within <strong>10 business days</strong>. If the complaint cannot be resolved within this period, we may extend by an additional 10 business days — you will be informed of the extension and the reasons why.</li>
  <li>If your complaint requires engagement of a third party, the final decision will be given within <strong>8 weeks</strong>.</li>
  <li>For complaints related to personal data processing, we follow the GDPR timeframe and respond within <strong>30 days</strong>.</li>
</ul>
<p>If you are not satisfied with our response regarding Personal Data, or believe we are processing your Personal Data unlawfully, you may submit a claim to the Estonian Data Protection Inspectorate (Andmekaitse Inspektsioon) at info[at]aki.ee (https://www.aki.ee/).</p>
<p><strong>ONLINE DISPUTE RESOLUTION</strong></p>
<p>You always have the right to use the EU Online Dispute Resolution (ODR) platform to lodge a consumer complaint: https://ec.europa.eu/consumers/odr/</p>',
    'aff_from_2025'  => '',
    'aff_until_2025' => '',
    'aff_until_2024' => '',
    'cpa_from'       => '',
    'cpa_until'      => '',
    'gdpr'           => '',
    'refund'         => '',
  ];
  ?>

  <?php foreach ($legalDocs as $ld): ?>
  <div id="modal-<?= $ld['id'] ?>" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeLegalModal('<?= $ld['id'] ?>')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col z-10">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <h3 class="font-bold text-slate-900 text-sm leading-snug pr-4"><?= htmlspecialchars($ld['title']) ?></h3>
        <button onclick="closeLegalModal('<?= $ld['id'] ?>')" class="flex-shrink-0 text-slate-400 hover:text-slate-600 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="overflow-y-auto px-5 py-4 text-sm text-slate-700 leading-relaxed flex-1 prose prose-sm max-w-none">
        <?= $legalContents[$ld['id']] ?: '<p class="text-slate-400 italic">Content coming soon.</p>' ?>
      </div>
      <div class="px-5 py-4 border-t border-slate-100">
        <button onclick="closeLegalModal('<?= $ld['id'] ?>')" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-semibold py-2.5 rounded-xl transition text-sm">Okay</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <script>
    function openLegalModal(id) {
      const m = document.getElementById('modal-' + id);
      if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    }
    function closeLegalModal(id) {
      const m = document.getElementById('modal-' + id);
      if (m) { m.classList.add('hidden'); document.body.style.overflow = ''; }
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('[id^="modal-"]').forEach(m => { m.classList.add('hidden'); });
        document.body.style.overflow = '';
      }
    });
  </script>

  <main class="max-w-xl mx-auto px-4 py-6">

    <!-- Legal Information Section -->
    <section class="bg-white border border-slate-200 rounded-2xl p-5 mb-6">
      <h2 class="font-bold text-slate-900 text-base mb-1">Legal Information</h2>
      <p class="text-xs text-slate-500 mb-4">This page contains a list of documents regulating the activity of 3Commas.</p>
      <ul class="divide-y divide-slate-100">
        <?php foreach ($legalDocs as $ld): ?>
        <li>
          <button onclick="openLegalModal('<?= $ld['id'] ?>')"
            class="w-full text-left py-2.5 text-sm text-emerald-700 hover:text-emerald-600 font-medium flex items-center justify-between gap-2 group transition">
            <span><?= htmlspecialchars($ld['title']) ?></span>
            <svg class="w-4 h-4 text-slate-300 group-hover:text-emerald-400 flex-shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </li>
        <?php endforeach; ?>
      </ul>
    </section>

    <?php if (empty($docs)): ?>
      <div class="text-center py-16">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
        </div>
        <p class="text-slate-500 font-medium">No documents available yet.</p>
        <p class="text-slate-400 text-sm mt-1">Check back later for terms, policies and reports.</p>
      </div>
    <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($docs as $doc): ?>
      <div class="bg-white border border-slate-200 rounded-2xl p-4 flex items-center justify-between gap-4 hover:border-emerald-300 transition">
        <div class="flex items-center gap-3 min-w-0">
          <?php
            $ext = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
            $iconColor = match($ext) {
                'pdf'  => 'text-red-500',
                'doc', 'docx' => 'text-blue-500',
                'xls', 'xlsx' => 'text-emerald-500',
                default => 'text-slate-400',
            };
          ?>
          <div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 <?= $iconColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-slate-900 text-sm truncate"><?= sanitize($doc['title']) ?></p>
            <?php if (!empty($doc['description'])): ?>
              <p class="text-xs text-slate-500 truncate"><?= sanitize($doc['description']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-slate-400 mt-0.5"><?= date('M j, Y', strtotime($doc['created_at'])) ?></p>
          </div>
        </div>
        <a href="../<?= sanitize($doc['file_path']) ?>"
          target="_blank" rel="noopener noreferrer"
          class="flex-shrink-0 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold px-3 py-2 rounded-lg transition flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Download
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </main>

</body>
</html>
