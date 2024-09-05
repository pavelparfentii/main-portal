const Pulsar = require('pulsar-client');

const { PULSAR_SERVICE_URL, PULSAR_TOKEN, PULSAR_MESSAGE_URL, PULSAR_TOPIC_NAME } = require('dotenv')
    .config({ path: __dirname + '/../.env' }).parsed;

(async () => {
    const fetch = (await import('node-fetch')).default;
    const client = new Pulsar.Client({
        serviceUrl: PULSAR_SERVICE_URL,
        authentication: new Pulsar.AuthenticationToken({
            token: PULSAR_TOKEN,
        }),
    });

    const consumer = await client.subscribe({
        // topic: 'persistent://di-auth/default/socials',
        topic: 'persistent://di-auth/default/'+ PULSAR_TOPIC_NAME,
        subscription: 'subscription',
        subscriptionType: 'Exclusive',
    });

    while (true) {
        const msg = await consumer.receive();
        try {
            const payload = msg.getData().toString();
            console.log(payload);
            await fetch(PULSAR_MESSAGE_URL, {
                method: 'POST',
                body: JSON.stringify(payload),
                headers: { 'Content-Type': 'application/json' },
            });
            consumer.acknowledge(msg);
        } catch (error) {
            console.error('Failed to process message', error);
        }
    }
})();
