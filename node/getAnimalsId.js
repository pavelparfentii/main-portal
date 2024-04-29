import web3 from "web3";
import fetch from 'node-fetch';
const user = process.argv[2];

import contractABI from '../node/DAContractABI/contractABI.js';

async function fetchApiKey() {
    const response = await fetch('https://safesoul.test-dev.site/api/airdrop/infuraKey');
    const data = await response.json(); // Assuming the key is returned in JSON format
    // console.log(data);

    return data.key; // Adjust according to your actual API response structure
}

async function getWeb3WithNextApiKey() {
    try {
        const nextApiKey = await fetchApiKey();
        return new web3(
            new web3.providers.HttpProvider(
                "https://mainnet.infura.io/v3/" + nextApiKey
            )
        );
    } catch (error) {
        console.error('Failed to create web3 instance with the next API key:', error);
        throw error;
    }
}

// const web3Eth = getWeb3WithNextApiKey();

async function initializeContract() {
    const web3Instance = await getWeb3WithNextApiKey(); // Await the web3 instance
    const contract = new web3Instance.eth.Contract(
        contractABI,
        "0x25593A50255Bfb30eA027f6966417b0BF780401d"
    );
    return contract;
}


async function loadUserTokens() {
    try {
        let contract = await initializeContract(); // Await the contract initialization
        let userTokensCount = await contract.methods.balanceOf(user).call();

        const tokensPromises = Array.from({ length: Number(userTokensCount) }, (_, index) =>
            contract.methods.tokenOfOwnerByIndex(user, index).call()
        );

        const tokens = await Promise.all(tokensPromises);
        const tokenNumbers = tokens.map(token => parseInt(token, 10));
        console.log(tokenNumbers);
    } catch (error) {
        console.error('Failed to load user tokens:', error);
    }
}

loadUserTokens();
