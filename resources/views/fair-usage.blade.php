<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>8ohm.co.za | Fair Usage Policy</title>

</head>

<body>
    <h1 id="fair-usage-policy">Fair Usage Policy</h1>
    <p><em>Last Updated: July 2026</em></p>

    <h2 id="1-overview-purpose">1. Overview &amp; Purpose</h2>
    <p>This Fair Usage Policy ("<strong>Policy</strong>") sets out the acceptable standards of resource consumption for
        all subscribers and users of the SaaS analytics platform, including the web-based analytics dashboard
        ("<strong>Dashboard</strong>") and Application Programming Interfaces ("<strong>API</strong>"), provided by
        <strong>Infinity Ohm Technologies (Pty) Ltd t/a 8OHM</strong> ("we," "us," or "our").</p>
    <p>Our platform operates on a shared, multi-tenant cloud infrastructure. This Policy is designed to ensure that the
        activity of a small number of high-volume users does not compromise the security, responsiveness, availability,
        or performance of the platform for all other customers.</p>
    <p>This Policy forms an integral part of our Terms of Service and is governed in accordance with the laws of the
        Republic of South Africa, including the Electronic Communications and Transactions Act 25 of 2002 (ECTA) and the
        Consumer Protection Act 68 of 2008 (CPA).</p>

    <h2 id="2-scope-standard-usage-parameters">2. Scope &amp; Standard Usage Parameters</h2>

    <h3 id="21-api-usage-guidelines">2.1 API Usage Guidelines</h3>
    <p>Fair usage of our API is governed by the specific subscription plan assigned to your account. Unless explicitly
        specified in a custom Enterprise Agreement, standard API parameters include:</p>
    <ul>
        <li><strong>Rate Limits:</strong> Maximum allowable API requests per minute (RPM) and per day (RPD) as detailed
            in your tier agreement.</li>
        <li><strong>Polling Frequency:</strong> Automated polling must adhere to standard time intervals. Rapid or
            excessive "heartbeat" polling that impacts server processing is prohibited.</li>
        <li><strong>Payload &amp; Query Complexity:</strong> Unusually heavy payloads, unindexed queries, or continuous
            bulk data exports that strain processing bandwidth fall outside standard fair usage limits.</li>
    </ul>

    <h3 id="22-analytics-dashboard-usage-guidelines">2.2 Analytics Dashboard Usage Guidelines</h3>
    <p>Fair usage of the Dashboard GUI includes:</p>
    <ul>
        <li><strong>Report Generation &amp; Data Exports:</strong> Generating complex reports or exporting large data
            sets in continuous automated loops via the interface is prohibited.</li>
        <li><strong>Session Integrity:</strong> Automated UI scraping, headless browser querying of dashboard elements,
            or multi-user credential sharing to bypass tier user limits is strictly prohibited.</li>
    </ul>

    <h2 id="3-monitoring-excessive-usage">3. Monitoring &amp; Excessive Usage</h2>
    <p>We continuously monitor system performance, server capacity, API request rates, and resource utilization across
        all accounts.</p>
    <p><strong>Excessive Usage</strong> occurs when an account consistently consumes processing power, memory,
        bandwidth, or request volumes significantly higher than average utilization for that subscription tier, or
        regularly exceeds published API rate limits.</p>

    <h2 id="4-remediation-consequences-of-excessive-usage">4. Remediation &amp; Consequences of Excessive Usage</h2>
    <p>Should your account consistently exceed fair usage thresholds or disrupt system integrity, the following actions
        will be applied:</p>

    <blockquote>
        <p><strong>⚠️ Key Terms Notice:</strong></p>
        <p><strong>1. Subscription Tier Adjustment &amp; Price Increases:</strong> If your usage regularly exceeds fair
            limits, you may choose to upgrade to a higher subscription tier or enterprise plan suitable for your volume.
            Continued sustained high-volume usage without a formal tier upgrade will result in an automatic price
            adjustment or overage charges billed on your next invoicing cycle, as permitted under your agreement.</p>
        <p><strong>2. Service Restriction or Disabling:</strong> We reserve the right to immediately throttle API
            response rates, restrict dashboard functions, or <strong>disable/suspend your service access
                entirely</strong> if high usage degrades system performance for other users or if you decline to adjust
            your subscription to match your actual consumption.</p>
    </blockquote>

    <h3 id="41-tier-enforcement-workflow">4.1 Tier Enforcement Workflow</h3>
    <ol>
        <li><strong>Initial Notification:</strong> We will issue a notice to your registered administrative email
            outlining the excessive resource usage and suggesting suitable tier upgrades.</li>
        <li><strong>Grace Period:</strong> You will be granted a <strong>7 to 14 day grace period</strong> from notice
            delivery to optimize your resource calls or execute an upgraded subscription agreement.</li>
        <li><strong>Price Revision or Service Disabling:</strong>
            <ul>
                <li><strong>Option A (Continued Service):</strong> If you choose to continue operating at elevated usage
                    levels, your subscription pricing will be automatically adjusted to the corresponding usage tier.
                </li>
                <li><strong>Option B (Throttling / Suspension):</strong> If you do not optimize usage, upgrade your
                    plan, or pay revised subscription fees, we reserve the right to apply API rate-limiting (throttling)
                    or temporarily/permanently disable account access without further notice.</li>
            </ul>
        </li>
    </ol>
    <p><em>Note: In extreme cases where excessive usage threatens system stability or resembles a Denial of Service
            (DoS) attack, we reserve the right to temporarily isolate or disable API endpoints immediately prior to
            issuing formal notice.</em></p>

    <h2 id="5-prohibited-activities">5. Prohibited Activities</h2>
    <p>Subscribers may not engage in any activity that compromises our platform's operations. Prohibited conduct
        includes:</p>
    <ul>
        <li>Attempting to circumvent API rate limits, query locks, or security parameters.</li>
        <li>Reverse-engineering API endpoints or utilizing UI scraping tools on the Analytics Dashboard.</li>
        <li>Sharing API keys or login credentials with unauthorized third parties.</li>
        <li>Conducting unauthorized stress testing, vulnerability scans, or load testing without prior written
            permission.</li>
    </ul>

    <h2 id="6-protection-of-personal-information-popia">6. Protection of Personal Information (POPIA)</h2>
    <p>Data extracted through our Dashboard or API must be handled in full compliance with the Protection of Personal
        Information Act 4 of 2013 (POPIA). Users are responsible for ensuring that all data queried, processed, or
        exported complies with South African privacy laws.</p>

    <h2 id="7-revisions-to-policy">7. Revisions to Policy</h2>
    <p>We reserve the right to update or modify this Fair Usage Policy periodically to reflect changes in legal
        requirements, platform updates, or infrastructure demands. Material changes will be communicated via email or
        through an in-app notice.</p>

    <h2 id="8-contact-details">8. Contact Details &amp; Support</h2>
    <p>If you have questions regarding your current resource usage, require custom enterprise limits, or wish to report
        a service bottleneck, please contact:</p>
    <ul>
        <li><strong>Company Name:</strong> Infinity Ohm Technologies (Pty) Ltd t/a 8OHM</li>
        <li><strong>Contact Channel:</strong> Secure contact form on <a href="https://www.8ohm.co.za">www.8ohm.co.za</a>
        </li>
    </ul>
    <hr>
</body>

</html>