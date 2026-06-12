<?php

-- ==== bastix - current day - EWH ====
SELECT agency, COUNT(bas) AS value
FROM bastix
WHERE datum >= CURDATE()
  AND agency LIKE '%_ewh'
GROUP BY agency;

SELECT agency, COUNT(bas) AS value
FROM bastix
WHERE datum >= CURDATE()
  AND agency LIKE '%_ewh'
  AND success = 0
GROUP BY agency;

SELECT agency, COUNT(DISTINCT bas) AS value
FROM bastix
WHERE datum >= CURDATE()
  AND agency LIKE '%_ewh'
  AND PriceDeviation > 10
  AND success = 1
GROUP BY agency;

SELECT agency, AVG(TIMESTAMPDIFF(SECOND, startt, endd)) AS value
FROM bastix
WHERE datum >= CURDATE()
  AND agency LIKE '%_ewh'
  AND startt IS NOT NULL
  AND endd IS NOT NULL
  AND success = 1
GROUP BY agency;

-- ==== bastix - last hour - EWH ====
SELECT agency, COUNT(bas) AS value
FROM bastix
WHERE datum >= NOW() - INTERVAL 1 HOUR
  AND agency LIKE '%_ewh'
GROUP BY agency;

-- Continue with the same patterns using success = 0, PriceDeviation > 10, and average time

-- ==== bastix - hour before last - EWH ====
SELECT agency, COUNT(bas) AS value
FROM bastix
WHERE datum BETWEEN NOW() - INTERVAL 2 HOUR AND NOW() - INTERVAL 1 HOUR
  AND agency LIKE '%_ewh'
GROUP BY agency;

-- Continue with the same patterns using success = 0, PriceDeviation > 10, and average time

-- ==== tix - current day - EWH ====
SELECT marke,
       COUNT(*) AS total,
       SUM(sts = 'ORDER_OK') AS ORDER_OK,
       SUM(CASE WHEN sts IN ('CREDITCARD_FAIL', 'BOOKING_FAIL', 'POOR_CREDITWORTHINESS', 'S_ORDER_OK') THEN 1 ELSE 0 END) AS notOk,
       SUM(CASE WHEN sts = 'ORDER_OK' THEN adult + child ELSE 0 END) AS orderTixOk
FROM tix
WHERE datum >= CURDATE()
  AND (traveltype IS NULL OR traveltype != 'HLM')
GROUP BY marke;

-- ==== tix - current day - HLM ====
SELECT marke,
       COUNT(*) AS total,
       SUM(sts = 'ORDER_OK') AS ORDER_OK,
       SUM(CASE WHEN sts IN ('CREDITCARD_FAIL', 'BOOKING_FAIL', 'POOR_CREDITWORTHINESS', 'S_ORDER_OK') THEN 1 ELSE 0 END) AS notOk,
       SUM(CASE WHEN sts = 'ORDER_OK' THEN adult + child ELSE 0 END) AS orderTixOk
FROM tix
WHERE datum >= CURDATE()
  AND traveltype = 'HLM'
GROUP BY marke;