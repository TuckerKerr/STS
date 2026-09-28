async function sendTeamsMessage() {
  const payload = {    
      "title": "Alert from webhook",
      "message": "<at>Thomas J Harrington (TJHarrington)</at> This is a test message coming from my code and I am testing the calling a person."
  };

  const response = await fetch(webhookUrl, {
      method: 'POST',
      headers: {
      'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
  });

  console.log('Status:', response.status);
  }

  sendTeamsMessage();