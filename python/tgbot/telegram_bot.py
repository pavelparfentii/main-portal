from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup, KeyboardButton, ReplyKeyboardMarkup
from telegram.ext import ApplicationBuilder, CommandHandler, MessageHandler, filters, ContextTypes

# Replace 'YOUR_BOT_TOKEN' with your actual bot token
BOT_TOKEN = '7475491207:AAEic6gcNnNijXx93qqTtDIMZOQrAqSnG1M'

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    # Get the user's first name and last name
    user_first_name = update.message.from_user.first_name
    user_last_name = update.message.from_user.last_name

    # Create inline keyboard buttons
    inline_keyboard = [
        [InlineKeyboardButton("🎮 Launch app", url="https://t.me/Souls_Club_bot/SCLUB")],
        [InlineKeyboardButton("💬 Chat", url="https://t.me/+YqZWK8A9lV1iNTIy")],
        [InlineKeyboardButton("💎 souls.club channel", url="https://t.me/soulsclub")],
        [InlineKeyboardButton("🌐 About souls.club", url="https://souls.club/airdrop")]
    ]

    # Create the inline keyboard markup
    inline_reply_markup = InlineKeyboardMarkup(inline_keyboard)

    # Send the message with the inline keyboard
    await update.message.reply_text(
        f'gmgn {user_first_name} {user_last_name}! It\'s great to see you at Souls.Club! 💎 Your hub for earning Diamonds through mini-games and tasks!\n\n'
        'Check out our new Telegram mini app! Begin farming Diamonds now and enjoy your rewards! 🎁\n\n'
        'Got friends? Bring them along! The more, the merrier!',
        reply_markup=inline_reply_markup
    )

    # Create custom keyboard buttons
    custom_keyboard = [
        [KeyboardButton("🎮 Launch app"), KeyboardButton("💬 Chat"), KeyboardButton("💎 souls.club channel")],
        [KeyboardButton("🌐 About souls.club")]
    ]

    # Create the custom keyboard markup
    custom_reply_markup = ReplyKeyboardMarkup(custom_keyboard, resize_keyboard=True)

    # Send another message with the custom keyboard
    await update.message.reply_text(
        reply_markup=custom_reply_markup
    )

# Handlers for custom keyboard button presses
async def handle_launch_app(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    keyboard = [
        [InlineKeyboardButton("🎮 Launch app", url="https://t.me/Souls_Club_bot/SCLUB")]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    await update.message.reply_text("Click the button below to launch the app:", reply_markup=reply_markup)

async def handle_chat(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    keyboard = [
        [InlineKeyboardButton("💬 Chat", url="https://t.me/+YqZWK8A9lV1iNTIy")]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    await update.message.reply_text("Click the button below to join the chat:", reply_markup=reply_markup)

async def handle_channel(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    keyboard = [
        [InlineKeyboardButton("💎 souls.club channel", url="https://t.me/soulsclub")]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    await update.message.reply_text("Click the button below to visit the channel:", reply_markup=reply_markup)

async def handle_about(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    keyboard = [
        [InlineKeyboardButton("🌐 About souls.club", url="https://souls.club/airdrop")]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    await update.message.reply_text("Click the button below to learn more about us:", reply_markup=reply_markup)

# Main function to start the bot
def main() -> None:
    # Create the Application
    application = ApplicationBuilder().token(BOT_TOKEN).build()


    application.add_handler(MessageHandler(filters.TEXT & ~filters.Regex("🎮 Launch app|💬 Chat|💎 souls.club channel|🌐 About souls.club"), start))

    # Register handlers for custom keyboard buttons
    application.add_handler(MessageHandler(filters.TEXT & filters.Regex("🎮 Launch app"), handle_launch_app))
    application.add_handler(MessageHandler(filters.TEXT & filters.Regex("💬 Chat"), handle_chat))
    application.add_handler(MessageHandler(filters.TEXT & filters.Regex("💎 souls.club channel"), handle_channel))
    application.add_handler(MessageHandler(filters.TEXT & filters.Regex("🌐 About souls.club"), handle_about))

    # Start the bot
    application.run_polling()

if __name__ == '__main__':
    main()
