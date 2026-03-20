using System.Windows.Forms;

namespace DotPos.SchemaEditor;

internal sealed class TextPromptDialog : Form
{
    private readonly TextBox _valueTextBox;

    public TextPromptDialog(string title, string prompt, string initialValue = "")
    {
        Text = title;
        FormBorderStyle = FormBorderStyle.FixedDialog;
        StartPosition = FormStartPosition.CenterParent;
        MinimizeBox = false;
        MaximizeBox = false;
        ShowInTaskbar = false;
        Width = 420;
        Height = 160;

        var promptLabel = new Label
        {
            AutoSize = true,
            Text = prompt,
            Left = 12,
            Top = 14
        };

        _valueTextBox = new TextBox
        {
            Left = 12,
            Top = 40,
            Width = 380,
            Text = initialValue
        };

        var okButton = new Button
        {
            Text = "OK",
            DialogResult = DialogResult.OK,
            Left = 236,
            Width = 75,
            Top = 78
        };

        var cancelButton = new Button
        {
            Text = "Cancel",
            DialogResult = DialogResult.Cancel,
            Left = 317,
            Width = 75,
            Top = 78
        };

        AcceptButton = okButton;
        CancelButton = cancelButton;

        Controls.Add(promptLabel);
        Controls.Add(_valueTextBox);
        Controls.Add(okButton);
        Controls.Add(cancelButton);
    }

    public string EnteredValue => _valueTextBox.Text.Trim();

    public static string? Show(IWin32Window owner, string title, string prompt, string initialValue = "")
    {
        using var dialog = new TextPromptDialog(title, prompt, initialValue);
        return dialog.ShowDialog(owner) == DialogResult.OK
            ? dialog.EnteredValue
            : null;
    }
}
